<?php

declare(strict_types=1);

namespace App\Modules\Review\Query\Review\GetById;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\Review\ReadModel\Review\ReviewDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class ReviewGetByIdFetcher
{
    private const string TABLE = 'reviews';
    private const int CACHE_TTL = 900;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
    ) {}

    /**
     * @throws Exception
     */
    public function fetch(ReviewGetByIdQuery $query): ReviewDetails
    {
        $key = 'review_by_id_' . $query->id;

        /** @var ReviewDetails|null $cached */
        $cached = $this->cacher->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $row = $this->connection->createQueryBuilder()
            ->select(...ReadModelFields::select(ReviewDetails::fields(), 'r'))
            ->from(self::TABLE, 'r')
            ->where('r.id = :id')
            ->andWhere('r.deleted_at IS NULL')
            ->setParameter('id', $query->id)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            throw new DomainExceptionModule(
                module: 'review',
                message: 'error.review_not_found',
                code: 1,
            );
        }

        /** @var array{id: string, product_id: int|string, user_id: string|null, user_name: string, rating: int|string, text: string, images: list<string>|string|null, source: string, is_approved: bool|int|string, created_at: string, updated_at: string|null} $row */
        $review = ReviewDetails::fromRow($row);
        $this->cacher->set($key, $review, self::CACHE_TTL);

        return $review;
    }
}
