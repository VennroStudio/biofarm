<?php

declare(strict_types=1);

namespace App\Modules\Page\Query\Page\FindAll;

use App\Components\ReadModel\ModelCountItemsResult;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\Page\ReadModel\Page\PageDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class PageFindAllFetcher
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @return ModelCountItemsResult<PageDetails>
     * @throws Exception
     */
    public function fetch(PageFindAllQuery $query): ModelCountItemsResult
    {
        $qb = $this->connection->createQueryBuilder()
            ->select(...ReadModelFields::select(PageDetails::fields(), 'p'))
            ->from('pages', 'p')
            ->where('p.deleted_at IS NULL');

        if (!$query->includeUnpublished) {
            $qb->andWhere('p.is_published = 1');
        }

        $rows = $qb
            ->orderBy("FIELD(p.page_type, 'system', 'custom')", 'ASC')
            ->addOrderBy('p.sort_order', 'ASC')
            ->addOrderBy('p.title', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        /** @var list<PageDetails> $items */
        $items = PageDetails::fromRows($rows);

        return new ModelCountItemsResult($items, \count($items));
    }
}
