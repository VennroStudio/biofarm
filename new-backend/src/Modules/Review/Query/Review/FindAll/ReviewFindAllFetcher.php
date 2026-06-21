<?php

declare(strict_types=1);

namespace App\Modules\Review\Query\Review\FindAll;

use App\Components\Cacher\Cacher;
use App\Components\ReadModel\ModelCountItemsResult;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\Review\ReadModel\Review\ReviewDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class ReviewFindAllFetcher
{
    private const string TABLE = 'reviews';
    private const int CACHE_TTL = 900;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
    ) {}

    /**
     * @return ModelCountItemsResult<ReviewDetails>
     * @throws Exception
     */
    public function fetch(ReviewFindAllQuery $query): ModelCountItemsResult
    {
        $key = 'reviews_find_all_' . ($query->onlyApproved ? 'approved' : 'any');

        /** @var ModelCountItemsResult<ReviewDetails>|null $cached */
        $cached = $this->cacher->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $qb = $this->connection->createQueryBuilder()
            ->from(self::TABLE, 'r')
            ->where('r.deleted_at IS NULL');

        if ($query->onlyApproved) {
            $qb->andWhere('r.is_approved = 1');
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(r.id)')->executeQuery()->fetchOne();

        $rows = $qb
            ->select(...ReadModelFields::select(ReviewDetails::fields(), 'r'))
            ->orderBy('r.created_at', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        /** @var list<ReviewDetails> $items */
        $items = ReviewDetails::fromRows($rows);
        $result = new ModelCountItemsResult($items, $total);
        $this->cacher->set($key, $result, self::CACHE_TTL);

        return $result;
    }
}
