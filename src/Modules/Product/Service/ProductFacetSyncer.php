<?php

declare(strict_types=1);

namespace App\Modules\Product\Service;

use App\Components\Exception\DomainExceptionModule;
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
        ?bool $isMainInGroup = null,
        bool $updateProductGroup = true,
    ): void {
        $attributeValueIds = $this->existingAttributeValueIds($this->ids($attributeValueIds));

        $this->syncAttributeValues($productId, $attributeValueIds);
        if ($updateProductGroup) {
            $this->syncProductGroup($productId, $productGroupId, $isMainInGroup);
        }
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

    private function syncProductGroup(int $productId, ?int $productGroupId, ?bool $isMainInGroup): void
    {
        $previous = $this->connection->fetchOne(
            'SELECT group_id FROM product_group_items WHERE product_id = ? FOR UPDATE',
            [$productId],
        );
        $previousGroupId = $previous === false ? null : (int) $previous;
        $groupIds = array_values(array_unique(array_filter([$previousGroupId, $productGroupId])));
        $groups = $groupIds === [] ? [] : $this->connection->fetchFirstColumn(
            'SELECT id FROM product_groups WHERE id IN (?) AND deleted_at IS NULL ORDER BY id FOR UPDATE',
            [$groupIds],
            [ArrayParameterType::INTEGER],
        );
        $groupId = in_array($productGroupId, array_map('intval', $groups), true) ? $productGroupId : null;
        if ($isMainInGroup === true && $groupId === null) {
            throw new DomainExceptionModule('product', 'error.main_product_group_required', 44, status: 422);
        }

        if ($previousGroupId !== $groupId) {
            if ($previousGroupId !== null) {
                $this->clearMainProduct($previousGroupId, $productId);
            }
            $this->connection->delete('product_group_items', ['product_id' => $productId]);
            if ($groupId !== null) {
                $this->connection->insert('product_group_items', [
                    'group_id' => $groupId,
                    'product_id' => $productId,
                    'created_at' => gmdate('Y-m-d H:i:s'),
                ]);
            }
        }

        if ($groupId !== null && $isMainInGroup !== null) {
            if ($isMainInGroup) {
                $this->connection->update('product_groups', ['main_product_id' => $productId], ['id' => $groupId]);
            } else {
                $this->clearMainProduct($groupId, $productId);
            }
        }
    }

    private function clearMainProduct(int $groupId, int $productId): void
    {
        $this->connection->update('product_groups', ['main_product_id' => null], [
            'id' => $groupId,
            'main_product_id' => $productId,
        ]);
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
