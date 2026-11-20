<?php

declare(strict_types=1);

namespace App\Modules\Product\Service;

use Doctrine\DBAL\Query\QueryBuilder;

final class CatalogProductVisibility
{
    /** Apply to public listings with products aliased as p. */
    public static function apply(QueryBuilder $query): void
    {
        $query->andWhere(<<<'SQL'
            NOT EXISTS (
                SELECT 1
                FROM product_group_items catalog_member
                INNER JOIN product_groups pg ON pg.id = catalog_member.group_id AND pg.deleted_at IS NULL
                INNER JOIN product_group_items main_member
                    ON main_member.group_id = pg.id AND main_member.product_id = pg.main_product_id
                INNER JOIN products main_product
                    ON main_product.id = main_member.product_id AND main_product.deleted_at IS NULL AND main_product.is_active = 1
                WHERE catalog_member.product_id = p.id AND main_product.id <> p.id
            )
            SQL);
    }
}
