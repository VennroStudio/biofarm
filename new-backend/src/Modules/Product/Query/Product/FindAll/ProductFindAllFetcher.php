<?php

declare(strict_types=1);

namespace App\Modules\Product\Query\Product\FindAll;

use App\Components\Cacher\Cacher;
use App\Components\ReadModel\ModelCountItemsResult;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\Product\ReadModel\Product\ProductDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class ProductFindAllFetcher
{
    private const string TABLE = 'products';
    private const int CACHE_TTL = 900;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
    ) {}

    /**
     * @return ModelCountItemsResult<ProductDetails>
     * @throws Exception
     */
    public function fetch(ProductFindAllQuery $query): ModelCountItemsResult
    {
        $key = $this->cacheKey($query);

        /** @var ModelCountItemsResult<ProductDetails>|null $cached */
        $cached = $this->cacher->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $qb = $this->connection->createQueryBuilder()
            ->from(self::TABLE, 'p')
            ->where('p.deleted_at IS NULL');

        if (!$query->includeInactive) {
            $qb->andWhere('p.is_active = 1');
        }

        if ($query->category !== null && $query->category !== '' && $query->category !== 'all') {
            $qb->andWhere('p.category_id = :category')
                ->setParameter('category', $query->category);
        }

        if ($query->search !== null && $query->search !== '') {
            $qb->andWhere('LOWER(p.name) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($query->search) . '%');
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(p.id)')->executeQuery()->fetchOne();

        $rows = $qb
            ->select(...ReadModelFields::select(ProductDetails::fields(), 'p'))
            ->orderBy('p.created_at', 'DESC')
            ->setFirstResult($query->getOffset())
            ->setMaxResults($query->perPage)
            ->executeQuery()
            ->fetchAllAssociative();

        /** @var list<ProductDetails> $items */
        $items = ProductDetails::fromRows($rows);
        $result = new ModelCountItemsResult($items, $total);

        $this->cacher->setTagged($key, $result, self::CACHE_TTL, ['products']);

        return $result;
    }

    private function cacheKey(ProductFindAllQuery $query): string
    {
        return sprintf(
            'products_find_all_%s_%s_%s_%d_%d',
            $query->category !== null && $query->category !== '' ? $query->category : 'all',
            $query->includeInactive ? 'with_inactive' : 'active',
            md5((string)$query->search),
            $query->page,
            $query->perPage,
        );
    }
}
