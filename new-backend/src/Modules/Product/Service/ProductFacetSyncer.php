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
     * @param list<int>|null $attributeValueIds
     * @param list<int>|null $componentIds
     * @param list<int>|null $purposeIds
     */
    public function sync(
        int $productId,
        ?array $attributeValueIds = null,
        ?array $componentIds = null,
        ?array $purposeIds = null,
        ?int $productGroupId = null,
    ): void
    {
        $attributeValueIds = $this->ids($attributeValueIds);
        if ($attributeValueIds === []) {
            $attributeValueIds = $this->attributeValueIdsFromLegacy($componentIds, $purposeIds);
        }

        $this->syncAttributeValues($productId, $attributeValueIds);
        $this->syncLegacyFacets($productId, $attributeValueIds);
        $this->syncProductGroup($productId, $productGroupId);
    }

    /**
     * @param list<int>|null $ids
     */
    private function syncAttributeValues(int $productId, array $ids): void
    {
        $this->connection->delete('product_attribute_values', ['product_id' => $productId]);

        foreach ($ids as $index => $attributeValueId) {
            $this->connection->insert('product_attribute_values', [
                'product_id' => $productId,
                'attribute_value_id' => $attributeValueId,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param list<int>|null $ids
     * @return list<int>
     */
    private function attributeValueIdsFromLegacy(?array $componentIds, ?array $purposeIds): array
    {
        $ids = [];
        foreach ($this->legacyAttributeValueIds('sostav', 'components', $this->ids($componentIds)) as $id) {
            $ids[$id] = $id;
        }

        foreach ($this->legacyAttributeValueIds('dlya', 'product_purposes', $this->ids($purposeIds)) as $id) {
            $ids[$id] = $id;
        }

        return array_values($ids);
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function legacyAttributeValueIds(string $attributeSlug, string $table, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->connection->createQueryBuilder()
            ->select('av.id')
            ->from($table, 'legacy')
            ->innerJoin('legacy', 'attributes', 'a', 'a.slug = :attributeSlug AND a.deleted_at IS NULL')
            ->innerJoin('legacy', 'attribute_values', 'av', 'av.attribute_id = a.id AND av.slug = legacy.slug AND av.deleted_at IS NULL')
            ->where('legacy.id IN (:ids)')
            ->setParameter('attributeSlug', $attributeSlug)
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchFirstColumn();

        return array_values(array_map(static fn (mixed $id): int => (int)$id, $rows));
    }

    /**
     * @param list<int> $attributeValueIds
     */
    private function syncLegacyFacets(int $productId, array $attributeValueIds): void
    {
        $this->connection->delete('product_components', ['product_id' => $productId]);
        $this->connection->delete('product_purpose_relations', ['product_id' => $productId]);

        if ($attributeValueIds === []) {
            return;
        }

        $rows = $this->connection->createQueryBuilder()
            ->select('av.id', 'av.slug', 'a.slug AS attribute_slug')
            ->from('attribute_values', 'av')
            ->innerJoin('av', 'attributes', 'a', 'a.id = av.attribute_id AND a.deleted_at IS NULL')
            ->where('av.id IN (:ids)')
            ->andWhere('av.deleted_at IS NULL')
            ->setParameter('ids', $attributeValueIds, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        $orderByValueId = array_flip($attributeValueIds);
        foreach ($rows as $row) {
            $sortOrder = (int)($orderByValueId[(int)$row['id']] ?? 0);
            if ($row['attribute_slug'] === 'sostav') {
                $componentId = $this->legacyIdBySlug('components', (string)$row['slug']);
                if ($componentId !== null) {
                    $this->connection->insert('product_components', [
                        'product_id' => $productId,
                        'component_id' => $componentId,
                        'sort_order' => $sortOrder,
                    ]);
                }
                continue;
            }

            if ($row['attribute_slug'] === 'dlya') {
                $purposeId = $this->legacyIdBySlug('product_purposes', (string)$row['slug']);
                if ($purposeId !== null) {
                    $this->connection->insert('product_purpose_relations', [
                        'product_id' => $productId,
                        'purpose_id' => $purposeId,
                        'sort_order' => $sortOrder,
                    ]);
                }
            }
        }
    }

    private function syncProductGroup(int $productId, ?int $productGroupId): void
    {
        $this->connection->delete('product_group_items', ['product_id' => $productId]);

        if ($productGroupId === null || $productGroupId <= 0 || !$this->productGroupExists($productGroupId)) {
            return;
        }

        $this->connection->insert('product_group_items', [
            'group_id' => $productGroupId,
            'product_id' => $productId,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    private function legacyIdBySlug(string $table, string $slug): ?int
    {
        $id = $this->connection->fetchOne(
            "SELECT id FROM {$table} WHERE slug = :slug AND deleted_at IS NULL LIMIT 1",
            ['slug' => $slug],
        );

        return $id !== false ? (int)$id : null;
    }

    private function productGroupExists(int $id): bool
    {
        return $this->connection->fetchOne(
            'SELECT id FROM product_groups WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => $id],
        ) !== false;
    }

    /**
     * @param list<int>|null $ids
     * @return list<int>
     */
    private function ids(?array $ids): array
    {
        $normalized = [];
        foreach ($ids ?? [] as $id) {
            $id = (int)$id;
            if ($id <= 0 || isset($normalized[$id])) {
                continue;
            }

            $normalized[$id] = $id;
        }

        return array_values($normalized);
    }
}
