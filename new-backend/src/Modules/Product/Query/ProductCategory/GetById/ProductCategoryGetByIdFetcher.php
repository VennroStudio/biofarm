<?php

declare(strict_types=1);

namespace App\Modules\Product\Query\ProductCategory\GetById;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\Product\ReadModel\ProductCategory\ProductCategoryDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class ProductCategoryGetByIdFetcher
{
    private const string TABLE = 'categories';
    private const int CACHE_TTL = 900;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
    ) {}

    /**
     * @throws Exception
     */
    public function fetch(ProductCategoryGetByIdQuery $query): ProductCategoryDetails
    {
        $key = 'category_by_id_' . $query->id;

        /** @var ProductCategoryDetails|null $cached */
        $cached = $this->cacher->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $row = $this->connection->createQueryBuilder()
            ->select(...ReadModelFields::select(ProductCategoryDetails::fields(), 'c'))
            ->from(self::TABLE, 'c')
            ->where('c.id = :id')
            ->andWhere('c.deleted_at IS NULL')
            ->setParameter('id', $query->id)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            throw new DomainExceptionModule(
                module: 'product',
                message: 'error.category_not_found',
                code: 1,
            );
        }

        /** @var array{id: int, slug: string, name: string, created_at: string, updated_at: string|null} $row */
        $category = ProductCategoryDetails::fromRow($row);
        $this->cacher->set($key, $category, self::CACHE_TTL);

        return $category;
    }
}
