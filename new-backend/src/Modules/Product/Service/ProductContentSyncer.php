<?php

declare(strict_types=1);

namespace App\Modules\Product\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class ProductContentSyncer
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @param list<int>|null $relatedBlogPostIds
     * @param list<int>|null $certificateIds
     */
    public function sync(
        int $productId,
        ?array $relatedBlogPostIds = null,
        ?array $certificateIds = null,
    ): void {
        if ($this->hasTable('product_blog_posts')) {
            $this->syncRelatedBlogPosts($productId, $this->existingBlogPostIds($relatedBlogPostIds));
        }

        if ($this->hasTable('certificates')) {
            $this->syncCertificates($productId, $this->existingCertificateIds($certificateIds));
        }
    }

    /**
     * @param list<int> $blogPostIds
     */
    private function syncRelatedBlogPosts(int $productId, array $blogPostIds): void
    {
        $this->connection->delete('product_blog_posts', ['product_id' => $productId]);

        foreach ($blogPostIds as $index => $blogPostId) {
            $this->connection->insert('product_blog_posts', [
                'product_id'   => $productId,
                'blog_post_id' => $blogPostId,
                'sort_order'   => $index,
            ]);
        }
    }

    /**
     * @param list<int> $certificateIds
     */
    private function syncCertificates(int $productId, array $certificateIds): void
    {
        $this->connection->update(
            'certificates',
            [
                'product_id' => null,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ],
            ['product_id' => $productId],
        );

        if ($certificateIds === []) {
            return;
        }

        $this->connection->executeStatement(
            'UPDATE certificates SET product_id = :productId, updated_at = :updatedAt WHERE id IN (:ids)',
            [
                'productId' => $productId,
                'updatedAt' => gmdate('Y-m-d H:i:s'),
                'ids'       => $certificateIds,
            ],
            ['ids' => ArrayParameterType::INTEGER],
        );
    }

    /**
     * @param list<int>|null $ids
     * @return list<int>
     */
    private function existingBlogPostIds(?array $ids): array
    {
        $ids = $this->ids($ids);
        if ($ids === []) {
            return [];
        }

        $existing = array_map(static fn (mixed $id): int => (int)$id, $this->connection->createQueryBuilder()
            ->select('id')
            ->from('blog_posts')
            ->where('id IN (:ids)')
            ->andWhere('deleted_at IS NULL')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchFirstColumn());

        return $this->orderedExistingIds($ids, $existing);
    }

    /**
     * @param list<int>|null $ids
     * @return list<int>
     */
    private function existingCertificateIds(?array $ids): array
    {
        $ids = $this->ids($ids);
        if ($ids === []) {
            return [];
        }

        $existing = array_map(static fn (mixed $id): int => (int)$id, $this->connection->createQueryBuilder()
            ->select('id')
            ->from('certificates')
            ->where('id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchFirstColumn());

        return $this->orderedExistingIds($ids, $existing);
    }

    private function hasTable(string $name): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$name]);
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

    /**
     * @param list<int> $requestedIds
     * @param list<int> $existingIds
     * @return list<int>
     */
    private function orderedExistingIds(array $requestedIds, array $existingIds): array
    {
        $existing = [];
        foreach ($existingIds as $id) {
            $existing[$id] = true;
        }

        return array_values(array_filter(
            $requestedIds,
            static fn (int $id): bool => isset($existing[$id]),
        ));
    }
}
