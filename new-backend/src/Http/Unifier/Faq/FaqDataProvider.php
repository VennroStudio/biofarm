<?php

declare(strict_types=1);

namespace App\Http\Unifier\Faq;

use App\Http\View\Faq\FaqItemView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class FaqDataProvider
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @return list<FaqItemView>
     * @throws Exception
     */
    public function forPage(string $scope, ?string $pageId = null, int $limit = 12): array
    {
        if (!$this->hasTable('faq_items')) {
            return [];
        }

        $scope = trim($scope);
        $pageId = $this->normalizePageId($pageId);

        $conditions = ['page_scope = :globalScope'];
        $params = ['globalScope' => 'global'];

        if ($scope !== '') {
            $conditions[] = $pageId !== null
                ? '(page_scope = :scope AND (page_id IS NULL OR page_id = :pageId))'
                : '(page_scope = :scope AND page_id IS NULL)';
            $params['scope'] = $scope;
            if ($pageId !== null) {
                $params['pageId'] = $pageId;
            }
        }

        /** @var list<array{id: int|string, question: string, answer: string}> $rows */
        $rows = $this->connection->createQueryBuilder()
            ->select('id', 'question', 'answer')
            ->from('faq_items')
            ->where('is_active = 1')
            ->andWhere('(' . implode(' OR ', $conditions) . ')')
            ->orderBy('sort_order', 'ASC')
            ->addOrderBy('id', 'ASC')
            ->setMaxResults($limit)
            ->setParameters($params)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(
            static fn (array $row): FaqItemView => new FaqItemView(
                id: (int)$row['id'],
                question: (string)$row['question'],
                answer: (string)$row['answer'],
            ),
            $rows,
        );
    }

    private function normalizePageId(?string $pageId): ?string
    {
        $pageId = trim((string)$pageId);

        return $pageId !== '' ? $pageId : null;
    }

    /**
     * @throws Exception
     */
    private function hasTable(string $name): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$name]);
    }
}
