<?php

declare(strict_types=1);

namespace App\Modules\Product\Query\Product\GetById;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\Product\ReadModel\Product\ProductDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class ProductGetByIdFetcher
{
    private const string TABLE = 'products';
    private const int CACHE_TTL = 900;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
    ) {}

    /**
     * @throws Exception
     */
    public function fetch(ProductGetByIdQuery $query): ProductDetails
    {
        $key = 'product_by_id_' . $query->id;

        /** @var ProductDetails|null $cached */
        $cached = $this->cacher->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $row = $this->connection->createQueryBuilder()
            ->select(...ReadModelFields::select(ProductDetails::fields(), 'p'))
            ->from(self::TABLE, 'p')
            ->where('p.id = :id')
            ->andWhere('p.deleted_at IS NULL')
            ->setParameter('id', $query->id)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            throw new DomainExceptionModule(
                module: 'product',
                message: 'error.product_not_found',
                code: 11,
            );
        }

        /** @var array{id: int|string, slug: string, name: string, category_id: string, price: int|string, old_price: int|string|null, image: string, images: list<string>|string|null, badge: string|null, weight: string, description: string, short_description: string|null, ingredients: string|null, features: list<string>|string|null, wb_link: string|null, ozon_link: string|null, is_active: int|string|bool, created_at: string, updated_at: string|null} $row */
        $product = ProductDetails::fromRow($row);
        $this->cacher->setTagged($key, $product, self::CACHE_TTL, ['products']);

        return $product;
    }
}
