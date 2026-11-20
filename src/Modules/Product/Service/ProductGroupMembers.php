<?php

declare(strict_types=1);

namespace App\Modules\Product\Service;

use App\Components\Exception\DomainExceptionModule;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class ProductGroupMembers
{
    private const string PRODUCT_FIELDS = 'p.id, p.name, COALESCE(pi.path, p.image) AS image, p.weight, p.sku, p.is_active, g.id AS group_id, g.name AS group_name';

    public function __construct(private Connection $db) {}

    public function details(int $id, bool $lock = false): array
    {
        $group = $this->db->fetchAssociative(
            'SELECT id, name, main_product_id FROM product_groups WHERE id = ? AND deleted_at IS NULL' . ($lock ? ' FOR UPDATE' : ''),
            [$id],
        );
        if ($group === false) {
            throw new DomainExceptionModule('product', 'error.product_group_not_found', 43, status: 404);
        }

        $products = $this->productRows('gi.group_id = ?', $id, 'gi.id', limit: null);
        $ids = array_column($products, 'id');
        $mainId = (int) $group['main_product_id'];

        return [
            'id' => $id,
            'name' => $group['name'],
            'products' => $products,
            'main_product_id' => in_array($mainId, $ids, true) ? $mainId : null,
            'revision' => hash('sha256', json_encode([$group['name'], $mainId, $ids], JSON_THROW_ON_ERROR)),
        ];
    }

    public function search(string $term): array
    {
        if ($term === '') {
            return [];
        }

        $term = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_substr($term, 0, 120));
        return $this->productRows("p.name LIKE ? ESCAPE '!'", '%' . $term . '%', 'p.name, p.id', limit: 30);
    }

    private function productRows(string $condition, int|string $value, string $order, ?int $limit): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT ' . self::PRODUCT_FIELDS . '
             FROM products p
             LEFT JOIN product_images pi ON pi.id = (
                 SELECT image.id FROM product_images image WHERE image.product_id = p.id AND image.is_main = 1 ORDER BY image.id LIMIT 1
             )
             LEFT JOIN product_group_items gi ON gi.product_id = p.id
             LEFT JOIN product_groups g ON g.id = gi.group_id AND g.deleted_at IS NULL
             WHERE p.deleted_at IS NULL AND ' . $condition . ' ORDER BY ' . $order . ($limit === null ? '' : ' LIMIT ' . $limit),
            [$value],
        );

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'image' => $row['image'],
            'weight' => $row['weight'],
            'sku' => $row['sku'],
            'is_active' => (bool) $row['is_active'],
            'group_id' => $row['group_id'] === null ? null : (int) $row['group_id'],
            'group_name' => $row['group_name'],
        ], $rows);
    }

    /** @param list<int> $productIds */
    public function replace(int $groupId, array $productIds, ?int $mainId): void
    {
        if ($mainId !== null && !in_array($mainId, $productIds, true)) {
            throw new DomainExceptionModule('product', 'error.product_group_main_not_member', 47, status: 422);
        }

        if ($productIds === []) {
            $this->db->delete('product_group_items', ['group_id' => $groupId]);
        } else {
            $this->replaceMemberships($groupId, $productIds);
        }
        $this->db->update('product_groups', ['main_product_id' => $mainId], ['id' => $groupId]);
    }

    /** @param list<int> $productIds */
    private function replaceMemberships(int $groupId, array $productIds): void
    {
        $products = $this->db->fetchFirstColumn(
            'SELECT id FROM products WHERE id IN (?) AND deleted_at IS NULL ORDER BY id FOR UPDATE',
            [$productIds],
            [ArrayParameterType::INTEGER],
        );
        if (count($products) !== count($productIds)) {
            throw new DomainExceptionModule('product', 'error.product_group_products_unavailable', 45, status: 422);
        }

        $this->db->executeStatement(
            'DELETE FROM product_group_items WHERE group_id = ? AND product_id NOT IN (?)',
            [$groupId, $productIds],
            [ParameterType::INTEGER, ArrayParameterType::INTEGER],
        );
        $memberships = $this->db->fetchAllKeyValue(
            'SELECT product_id, group_id FROM product_group_items WHERE product_id IN (?) FOR UPDATE',
            [$productIds],
            [ArrayParameterType::INTEGER],
        );
        $now = gmdate('Y-m-d H:i:s');
        foreach ($productIds as $productId) {
            $source = isset($memberships[$productId]) ? (int) $memberships[$productId] : null;
            if ($source === $groupId) {
                continue;
            }
            if ($source === null) {
                $this->db->insert('product_group_items', ['group_id' => $groupId, 'product_id' => $productId, 'created_at' => $now]);
                continue;
            }
            $this->db->executeStatement(
                'UPDATE product_groups SET main_product_id = NULL WHERE id = ? AND main_product_id = ?',
                [$source, $productId],
            );
            $this->db->update('product_group_items', ['group_id' => $groupId, 'updated_at' => $now], ['product_id' => $productId]);
        }
    }
}
