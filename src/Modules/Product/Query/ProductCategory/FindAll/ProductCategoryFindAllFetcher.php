<?php

declare(strict_types=1);

namespace App\Modules\Product\Query\ProductCategory\FindAll;

use App\Components\Cacher\Cacher;
use App\Components\ReadModel\ModelCountItemsResult;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\Product\ReadModel\ProductCategory\ProductCategoryDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class ProductCategoryFindAllFetcher
{
    private const string TABLE = 'categories';
    private const int CACHE_TTL = 900;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
    ) {}

    /**
     * @return ModelCountItemsResult<ProductCategoryDetails>
     * @throws Exception
     */
    public function fetch(ProductCategoryFindAllQuery $query): ModelCountItemsResult
    {
        $key = 'categories_find_all';

        /** @var ModelCountItemsResult<ProductCategoryDetails>|null $cached */
        $cached = $query->page === 1 && $query->perPage === 100
            ? $this->cacher->get($key)
            : null;

        if ($cached !== null) {
            return $cached;
        }

        $qb = $this->connection->createQueryBuilder()
            ->from(self::TABLE, 'c')
            ->where('c.deleted_at IS NULL');

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(c.id)')->executeQuery()->fetchOne();

        $rows = $qb
            ->select(...ReadModelFields::select(ProductCategoryDetails::fields(), 'c'))
            ->orderBy('COALESCE(c.parent_id, c.id)', 'ASC')
            ->addOrderBy('c.parent_id IS NOT NULL', 'ASC')
            ->addOrderBy('c.sort_order', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->setFirstResult($query->getOffset())
            ->setMaxResults($query->perPage)
            ->executeQuery()
            ->fetchAllAssociative();

        /** @var list<ProductCategoryDetails> $items */
        $items = ProductCategoryDetails::fromRows($rows);

        $result = new ModelCountItemsResult(
            items: $items,
            count: $total,
        );

        if ($query->page === 1 && $query->perPage === 100) {
            $this->cacher->set($key, $result, self::CACHE_TTL);
        }

        return $result;
    }
}
