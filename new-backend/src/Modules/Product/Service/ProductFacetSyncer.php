<?php

declare(strict_types=1);

namespace App\Modules\Product\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class ProductFacetSyncer
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @param array<array-key, mixed>|null $attributeValueIds
     */
    public function sync(
        int $productId,
        ?array $attributeValueIds = null,
        ?int $productGroupId = null,
    ): void {
        $attributeValueIds = $this->existingAttributeValueIds($this->ids($attributeValueIds));

        $this->syncAttributeValues($productId, $attributeValueIds);
        $this->syncProductGroup($productId, $productGroupId);
    }

    /**
     * @param list<int> $ids
     */
    private function syncAttributeValues(int $productId, array $ids): void
    {
        $this->connection->delete('product_attribute_values', ['product_id' => $productId]);

        foreach ($ids as $index => $attributeValueId) {
            $this->connection->insert('product_attribute_values', [
                'product_id'         => $productId,
                'attribute_value_id' => $attributeValueId,
                'sort_order'         => $index,
            ]);
        }
    }

    private function syncProductGroup(int $productId, ?int $productGroupId): void
    {
        $this->connection->delete('product_group_items', ['product_id' => $productId]);

        if ($productGroupId === null || $productGroupId <= 0 || !$this->productGroupExists($productGroupId)) {
            return;
        }

        $this->connection->insert('product_group_items', [
            'group_id'   => $productGroupId,
            'product_id' => $productId,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    private function productGroupExists(int $id): bool
    {
        return $this->connection->fetchOne(
            'SELECT id FROM product_groups WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => $id],
        ) !== false;
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function existingAttributeValueIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $existingIds = array_flip(array_map(
            static fn (mixed $id): int => (int)$id,
            $this->connection->fetchFirstColumn(
                'SELECT id FROM attribute_values WHERE id IN (:ids) AND deleted_at IS NULL',
                ['ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER],
            ),
        ));

        return array_values(array_filter(
            $ids,
            static fn (int $id): bool => isset($existingIds[$id]),
        ));
    }

    /**
     * @param array<array-key, mixed>|null $ids
     * @return list<int>
     */
    private function ids(?array $ids): array
    {
        $normalized = [];
        foreach (array_map(static fn (mixed $id): int => (int)$id, $ids ?? []) as $id) {
            if ($id <= 0 || isset($normalized[$id])) {
                continue;
            }

            $normalized[$id] = $id;
        }

        return array_values($normalized);
    }
}
