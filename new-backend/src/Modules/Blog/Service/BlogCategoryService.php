<?php

declare(strict_types=1);

namespace App\Modules\Blog\Service;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\String\SlugGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Platforms\SQLitePlatform;

final readonly class BlogCategoryService
{
    public function __construct(private Connection $connection, private Cacher $cacher, private SlugGenerator $slugs) {}

    public function all(): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT c.id, c.name, c.slug, c.sort_order, (SELECT COUNT(*) FROM blog_posts p WHERE p.category_id = c.slug AND p.deleted_at IS NULL) AS posts_count FROM blog_categories c WHERE c.deleted_at IS NULL ORDER BY c.sort_order, c.id');
        return array_map(static fn (array $row): array => [
            'id'         => (int)$row['id'], 'name' => $row['name'], 'slug' => $row['slug'],
            'sort_order' => (int)$row['sort_order'], 'posts_count' => (int)$row['posts_count'],
        ], $rows);
    }

    public function assertAvailable(string $slug): void
    {
        if (!$this->connection->fetchOne('SELECT id FROM blog_categories WHERE slug = ? AND deleted_at IS NULL', [trim($slug)])) {
            throw new DomainExceptionModule('blog', 'Выберите существующую категорию статьи.', 50, status: 422);
        }
    }

    public function save(?int $id, array $payload): int
    {
        $name = trim((string)($payload['name'] ?? ''));
        $slug = $this->slugs->generate(trim((string)($payload['slug'] ?? '')) ?: $name);
        $order = filter_var($payload['sort_order'] ?? 0, FILTER_VALIDATE_INT);
        if ($name === '' || mb_strlen($name) > 255 || $name === 'Все' || $slug === '' || \strlen($slug) > 255 || $order === false || $order < 0 || $order > 2147483647) {
            throw new DomainExceptionModule('blog', 'Укажите название, корректный адрес и неотрицательный порядок. Название «Все» зарезервировано.', 51, status: 422);
        }
        try {
            $savedId = $this->connection->transactional(function () use ($id, $name, $slug, $order): int {
                $current = $id === null ? null : $this->find($id);
                if ($this->connection->fetchOne('SELECT id FROM blog_categories WHERE name = ? AND deleted_at IS NULL AND id <> ?', [$name, $id ?? 0])) {
                    throw new DomainExceptionModule('blog', 'Категория с таким названием уже существует.', 52, status: 422);
                }
                $data = ['name' => $name, 'slug' => $slug, 'sort_order' => $order, 'updated_at' => gmdate('Y-m-d H:i:s')];
                if ($id !== null) {
                    $this->connection->update('blog_categories', $data, ['id' => $id]);
                    if ($current['slug'] !== $slug) {
                        $this->connection->update('blog_posts', ['category_id' => $slug], ['category_id' => $current['slug']]);
                    }
                    return $id;
                }
                $this->connection->insert('blog_categories', $data + ['is_indexable' => 1, 'created_at' => gmdate('Y-m-d H:i:s')]);
                return (int)$this->connection->lastInsertId();
            });
        } catch (UniqueConstraintViolationException $error) {
            throw new DomainExceptionModule('blog', 'Этот адрес категории уже занят. Укажите другой.', 53, status: 422, previous: $error);
        }
        $this->cacher->deleteTag('blog_posts');
        return $savedId;
    }

    public function delete(int $id): void
    {
        $this->connection->transactional(function () use ($id): void {
            $category = $this->find($id);
            if ($this->connection->fetchOne('SELECT id FROM blog_posts WHERE category_id = ? AND deleted_at IS NULL LIMIT 1', [$category['slug']])) {
                throw new DomainExceptionModule('blog', 'Сначала перенесите статьи в другую категорию.', 54, status: 409);
            }
            $this->connection->update('blog_categories', ['deleted_at' => gmdate('Y-m-d H:i:s')], ['id' => $id]);
        });
        $this->cacher->deleteTag('blog_posts');
    }

    private function find(int $id): array
    {
        $lock = $this->connection->getDatabasePlatform() instanceof SQLitePlatform ? '' : ' FOR UPDATE';
        $row = $this->connection->fetchAssociative('SELECT id, slug FROM blog_categories WHERE id = ? AND deleted_at IS NULL' . $lock, [$id]);
        if (!$row) {
            throw new DomainExceptionModule('blog', 'Категория не найдена.', 55, status: 404);
        }
        return $row;
    }
}
