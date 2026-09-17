<?php

declare(strict_types=1);

namespace App\Modules\Content\Service;

use App\Components\Exception\DomainExceptionModule;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;

final readonly class MaterialLibrary
{
    public function __construct(private Connection $connection) {}

    public function targets(array $query): array
    {
        $type = $query['target_type'] ?? null;
        $search = trim((string)($query['search'] ?? ''));
        $parts = [];
        if ($type === null || $type === '' || $type === 'product') {
            $parts[] = "SELECT 'product' target_type, id, name title FROM products WHERE deleted_at IS NULL";
        }
        if ($type === null || $type === '' || $type === 'page') {
            $parts[] = "SELECT 'page' target_type, id, title FROM pages WHERE deleted_at IS NULL";
        }
        if ($parts === []) {
            throw $this->error('invalid_target');
        }
        [$page,$perPage] = $this->pagination($query);
        $sql = ' FROM (' . implode(' UNION ALL ', $parts) . ') t WHERE title LIKE ?';
        $total = (int)$this->connection->fetchOne('SELECT COUNT(*)' . $sql, ['%' . $search . '%']);
        $rows = $this->connection->fetchAllAssociative('SELECT *' . $sql . ' ORDER BY title,target_type,id LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), ['%' . $search . '%']);
        return ['items' => array_map(fn (array $r): array => $this->target($r['target_type'], (string)$r['id']), $rows), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function listing(string $kind, array $query): array
    {
        $table = $this->table($kind);
        $where = ['1=1'];
        $params = [];
        $types = [];
        if (trim((string)($query['search'] ?? '')) !== '') {
            $where[] = ($kind === 'faq' ? '(m.question LIKE :search OR m.answer LIKE :search)' : '(m.title LIKE :search OR m.description LIKE :search)');
            $params['search'] = '%' . trim($query['search']) . '%';
        }
        if ($kind === 'certificate' && isset($query['category_id']) && $query['category_id'] !== '') {
            if ((int)$query['category_id'] === 0) {
                $where[] = 'm.category_id IS NULL';
            } else {
                $where[] = 'm.category_id=:category';
                $params['category'] = (int)$query['category_id'];
            }
        }
        if (isset($query['is_active']) && $query['is_active'] !== '') {
            $where[] = 'm.is_active=:active';
            $params['active'] = (int)(bool)$query['is_active'];
        }
        $exists = 'EXISTS (SELECT 1 FROM material_placements p WHERE p.kind=:kind AND p.material_id=m.id)';
        $params['kind'] = $kind;
        if (($query['usage'] ?? '') === 'used') {
            $where[] = $exists;
        }
        if (($query['usage'] ?? '') === 'unused') {
            $where[] = 'NOT ' . $exists;
        }
        if (!empty($query['target_type']) && empty($query['target_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM material_placements p WHERE p.kind=:kind AND p.material_id=m.id AND p.target_type=:target_type)';
            $params['target_type'] = $query['target_type'];
        }
        if (!empty($query['target_type']) && isset($query['target_id']) && $query['target_id'] !== '') {
            $where[] = 'm.id IN (:selected)';
            $params['selected'] = $this->selectionIds($kind, (string)$query['target_type'], (string)$query['target_id']);
            $types['selected'] = ArrayParameterType::INTEGER;
        }
        if (isset($query['ids']) && trim((string)$query['ids']) !== '') {
            $where[] = 'm.id IN (:ids)';
            $params['ids'] = $this->ids(explode(',', (string)$query['ids']));
            $types['ids'] = ArrayParameterType::INTEGER;
        }
        [$page,$perPage] = $this->pagination($query);
        $from = " FROM {$table} m LEFT JOIN material_categories c ON c.id=m.category_id WHERE " . implode(' AND ', $where);
        // Use a common kind parameter in both count and item query.
        $from .= ' AND :kind=:kind';
        $total = (int)$this->connection->fetchOne('SELECT COUNT(*)' . $from, $params, $types);
        $rows = $this->connection->fetchAllAssociative('SELECT m.*,c.name category_name,(SELECT COUNT(*) FROM material_placements p WHERE p.kind=:kind AND p.material_id=m.id) usage_count' . $from . ' ORDER BY m.id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $params, $types);
        return ['items' => array_map(fn (array $r): array => $this->normalize($kind, $r), $rows), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function get(string $kind, int $id): array
    {
        $table = $this->table($kind);
        $row = $this->connection->fetchAssociative("SELECT m.*,c.name category_name FROM {$table} m LEFT JOIN material_categories c ON c.id=m.category_id WHERE m.id=?", [$id]);
        if (!$row) {
            throw $this->error('not_found', 404);
        }
        $placements = [];
        foreach ($this->connection->fetchAllAssociative('SELECT target_type,target_id FROM material_placements WHERE kind=? AND material_id=? ORDER BY target_type,target_id', [$kind, $id]) as $p) {
            try {
                $placements[] = $this->target($p['target_type'], $p['target_id']);
            } catch (DomainExceptionModule) {
                $placements[] = $p + ['title' => 'Удалённый объект #' . $p['target_id'], 'url' => null];
            }
        }
        $row['usage_count'] = \count($placements);
        $row['placements'] = $placements;
        return $this->normalize($kind, $row);
    }

    public function save(string $kind, ?int $id, array $payload): int
    {
        return $this->connection->transactional(function () use ($kind, $id, $payload): int {
            $table = $this->table($kind);
            $old = $id !== null ? $this->get($kind, $id) : [];
            $data = [];
            foreach ($kind === 'faq' ? ['question', 'answer'] : ['title', 'file_path', 'document_type', 'description'] as $field) {
                $data[$field] = \array_key_exists($field, $payload) ? $payload[$field] : ($old[$field] ?? null);
            }
            foreach ($kind === 'faq' ? ['question', 'answer'] : ['title', 'file_path'] as $field) {
                $data[$field] = trim((string)$data[$field]);
                if ($data[$field] === '') {
                    throw $this->error($field . '_required');
                }
            }
            if ($kind === 'certificate') {
                if (!str_starts_with($data['file_path'], '/uploads/') || str_contains($data['file_path'], '..')) {
                    throw $this->error('file_invalid');
                }
                $data['document_type'] = \in_array($data['document_type'], ['pdf', 'image'], true) ? $data['document_type'] : (str_ends_with(strtolower($data['file_path']), '.pdf') ? 'pdf' : 'image');
                if (mb_strlen($data['title']) > 255 || mb_strlen($data['file_path']) > 512 || mb_strlen((string)$data['description']) > 500) {
                    throw $this->error('text_too_long');
                }
            } elseif (mb_strlen($data['question']) > 500) {
                throw $this->error('text_too_long');
            }
            $category = $kind === 'certificate' ? (\array_key_exists('category_id', $payload) ? $payload['category_id'] : ($old['category_id'] ?? null)) : null;
            if ($category !== null && !$this->connection->fetchOne('SELECT id FROM material_categories WHERE id=? AND kind=? FOR UPDATE', [(int)$category, $kind])) {
                throw $this->error('category_not_found');
            }
            $data['category_id'] = $category === null ? null : (int)$category;
            $data['is_active'] = (int)(bool)($payload['is_active'] ?? $old['is_active'] ?? true);
            $data['updated_at'] = gmdate('Y-m-d H:i:s');
            if ($id === null) {
                $data['created_at'] = $data['updated_at'];
                if ($kind === 'faq') {
                    $data['page_scope'] = 'library';
                } $this->connection->insert($table, $data);
                $id = (int)$this->connection->lastInsertId();
            } else {
                $this->connection->update($table, $data, ['id' => $id]);
            }
            if (\array_key_exists('placements', $payload)) {
                if (!\is_array($payload['placements'])) {
                    throw $this->error('invalid_placements');
                } $this->replacePlacements($kind, $id, $payload['placements']);
            }
            return $id;
        });
    }

    public function delete(string $kind, int $id): void
    {
        $this->connection->transactional(function () use ($kind, $id): void {
            $table = $this->table($kind);
            $this->lock($kind, [$id]);
            if ($this->connection->fetchOne('SELECT 1 FROM material_placements WHERE kind=? AND material_id=? LIMIT 1', [$kind, $id])) {
                throw $this->error('in_use');
            }
            $this->connection->delete($table, ['id' => $id]);
        });
    }

    public function syncTarget(string $type, string $id, string $kind, array $ids): void
    {
        $this->connection->transactional(function () use ($type, $id, $kind, $ids): void {
            $this->target($type, $id);
            $this->table($kind);
            $ids = $this->ids($ids);
            if ($type !== 'scope') {
                $targetTable = $type === 'product' ? 'products' : 'pages';
                $this->connection->fetchOne("SELECT id FROM {$targetTable} WHERE id=? FOR UPDATE", [$id]);
            }
            $this->lock($kind, $ids);
            $this->mark($kind, $type, $id);
            $this->connection->delete('material_placements', ['kind' => $kind, 'target_type' => $type, 'target_id' => $id]);
            foreach ($ids as $order => $materialId) {
                $this->connection->insert('material_placements', ['kind' => $kind, 'material_id' => $materialId, 'target_type' => $type, 'target_id' => $id, 'sort_order' => $order]);
            }
        });
    }

    public function bulkAttach(string $kind, array $ids, array $placements): void
    {
        $this->connection->transactional(function () use ($kind, $ids, $placements): void {
            $ids = $this->ids($ids);
            $this->lock($kind, $ids);
            foreach ($placements as $p) {
                $this->attach($kind, $ids, $p);
            }
        });
    }

    public function selections(string $type, string $id): array
    {
        $this->target($type, $id);
        return ['certificate_ids' => $this->selectionIds('certificate', $type, $id), 'faq_ids' => $this->selectionIds('faq', $type, $id)];
    }

    public function publicItems(string $kind, string $scope, ?string $key = null, ?int $limit = null): array
    {
        $table = $this->table($kind);
        $target = false;
        $type = 'page';
        if ($scope === 'product' && $key !== null) {
            $type = 'product';
            $target = $this->connection->fetchOne('SELECT id FROM products WHERE (slug=? OR id=?) AND deleted_at IS NULL', [$key, ctype_digit($key) ? (int)$key : 0]);
        } elseif ($scope === 'page' && $key !== null) {
            $path = trim((string)(parse_url($key, PHP_URL_PATH) ?? $key), '/');
            $system = match ($path) {
                '' => 'home','loyalnost' => 'loyalty','email-verification' => 'email_verification','order-success' => 'order_success',default => $path
            };
            $target = $this->connection->fetchOne("SELECT id FROM pages WHERE (TRIM(BOTH '/' FROM slug_path)=? OR system_key=?) AND deleted_at IS NULL AND is_published=1", [$path, $system]);
            if ($target === false) {
                return [];
            }
        } else {
            $target = $this->connection->fetchOne('SELECT id FROM pages WHERE system_key=? AND deleted_at IS NULL', [$scope]);
        }
        $ids = $target !== false ? $this->selectionIds($kind, $type, (string)$target) : $this->inherited($kind, $scope, $key);
        if ($ids === []) {
            return [];
        }
        $rows = $this->connection->fetchAllAssociative("SELECT * FROM {$table} WHERE id IN (?) AND is_active=1", [$ids], [ArrayParameterType::INTEGER]);
        $byId = array_column($rows, null, 'id');
        $result = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $result[] = $byId[$id];
            }
        }
        return $limit === null ? $result : \array_slice($result, 0, max(0, $limit));
    }

    public function categories(string $kind): array
    {
        $table = $this->table($kind);
        return ['items' => array_map(static fn (array $r): array => ['id' => (int)$r['id'], 'name' => $r['name'], 'usage_count' => (int)$r['usage_count']], $this->connection->fetchAllAssociative("SELECT c.id,c.name,COUNT(m.id) usage_count FROM material_categories c LEFT JOIN {$table} m ON m.category_id=c.id WHERE c.kind=? GROUP BY c.id,c.name ORDER BY c.name,c.id", [$kind]))];
    }

    public function saveCategory(string $kind, ?int $id, array $payload): int
    {
        $this->table($kind);
        $name = trim((string)($payload['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 255) {
            throw $this->error('category_name_required');
        }
        try {
            if ($id === null) {
                $this->connection->insert('material_categories', ['kind' => $kind, 'name' => $name]);
                return (int)$this->connection->lastInsertId();
            }
            if (!$this->connection->fetchOne('SELECT id FROM material_categories WHERE id=? AND kind=?', [$id, $kind])) {
                throw $this->error('category_not_found', 404);
            }
            $this->connection->update('material_categories', ['name' => $name], ['id' => $id, 'kind' => $kind]);
            return $id;
        } catch (UniqueConstraintViolationException) {
            throw $this->error('category_exists');
        }
    }

    public function deleteCategory(string $kind, int $id): void
    {
        $this->connection->transactional(function () use ($kind, $id): void {
            $table = $this->table($kind);
            if (!$this->connection->fetchOne('SELECT id FROM material_categories WHERE id=? AND kind=? FOR UPDATE', [$id, $kind])) {
                throw $this->error('category_not_found', 404);
            }
            if ($this->connection->fetchOne("SELECT id FROM {$table} WHERE category_id=? LIMIT 1", [$id])) {
                throw $this->error('category_in_use');
            }
            $this->connection->delete('material_categories', ['id' => $id, 'kind' => $kind]);
        });
    }

    private function table(string $kind): string
    {
        return match ($kind) {
            'certificate' => 'certificates', 'faq' => 'faq_items', default => throw $this->error('invalid_kind')
        };
    }

    private function error(string $message, int $status = 422): DomainExceptionModule
    {
        return new DomainExceptionModule('material', 'error.material_' . $message, 1, status: $status);
    }

    private function target(string $type, string $id): array
    {
        if ($type === 'scope') {
            if (!$this->connection->fetchOne("SELECT 1 FROM material_placements WHERE target_type='scope' AND target_id=? LIMIT 1", [$id])) {
                throw $this->error('target_not_found');
            }
            return ['target_type' => $type, 'target_id' => $id, 'title' => $this->scopeTitle($id), 'url' => null];
        }
        $table = match ($type) {
            'product' => 'products', 'page' => 'pages', default => throw $this->error('invalid_target')
        };
        if (!ctype_digit($id) || (int)$id < 1) {
            throw $this->error('invalid_target');
        }
        $row = $this->connection->fetchAssociative("SELECT * FROM {$table} WHERE id=? AND deleted_at IS NULL", [(int)$id]);
        if (!$row) {
            throw $this->error('target_not_found');
        }
        $url = $type === 'product' ? '/product/' . $row['slug'] : ($row['slug_path'] ?: match ($row['system_key']) {
            'home' => '/', 'email_verification' => '/email-verification', 'loyalty' => '/loyalnost', 'order_success' => '/order-success', default => '/' . $row['system_key']
        });
        return ['target_type' => $type, 'target_id' => (string)$row['id'], 'title' => $row[$type === 'product' ? 'name' : 'title'], 'url' => $url];
    }

    private function scopeTitle(string $id): string
    {
        [$scope,$key] = explode(':', $id, 2) + [1 => null];
        $label = match ($scope) {
            'global'  => 'Все страницы', 'home' => 'Главная', 'product' => 'Все товары', 'page' => 'Все информационные страницы',
            'catalog' => 'Каталог', 'category' => 'Все категории товаров', 'attribute' => 'Все фильтры каталога',
            'blog'    => 'Блог', 'post' => 'Все статьи', 'faq' => 'Вопросы и ответы', 'certificates' => 'Сертификаты',
            'loyalty' => 'Программа лояльности', 'email_verification' => 'Подтверждение почты', default => $scope,
        };
        return $key === null ? $label : $label . ': ' . $key;
    }

    private function pagination(array $query): array
    {
        return [max(1, (int)($query['page'] ?? 1)), (int)($query['per_page'] ?? 25) === 50 ? 50 : 25];
    }

    private function normalize(string $kind, array $row): array
    {
        $row['id'] = (int)$row['id'];
        $row['is_active'] = (bool)$row['is_active'];
        $row['category_id'] = $row['category_id'] !== null ? (int)$row['category_id'] : null;
        $row['usage_count'] = (int)($row['usage_count'] ?? 0);
        $row['title'] = $kind === 'faq' ? $row['question'] : $row['title'];
        $row['updated_at'] = (string)($row['updated_at'] ?? $row['created_at']);
        return $row;
    }

    private function ids(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            if (!\is_scalar($id) || !ctype_digit((string)$id) || (int)$id < 1) {
                throw $this->error('invalid_id');
            } $out[(int)$id] = (int)$id;
        } return array_values($out);
    }

    private function lock(string $kind, array $ids): void
    {
        if ($ids === []) {
            return;
        }
        $table = $this->table($kind);
        $found = $this->connection->fetchFirstColumn("SELECT id FROM {$table} WHERE id IN (?) ORDER BY id FOR UPDATE", [$ids], [ArrayParameterType::INTEGER]);
        if (\count($found) !== \count($ids)) {
            throw $this->error('not_found', 404);
        }
    }

    private function mark(string $kind, string $type, string $id): void
    {
        $this->connection->executeStatement('INSERT IGNORE INTO material_target_selections (kind,target_type,target_id) VALUES (?,?,?)', [$kind, $type, $id]);
        $this->connection->fetchOne('SELECT kind FROM material_target_selections WHERE kind=? AND target_type=? AND target_id=? FOR UPDATE', [$kind, $type, $id]);
    }

    private function replacePlacements(string $kind, int $id, array $placements): void
    {
        $this->lock($kind, [$id]);
        $desired = [];
        $old = $this->get($kind, $id)['placements'];
        $oldKeys = [];
        foreach ($old as $p) {
            $oldKeys[$p['target_type'] . '/' . $p['target_id']] = $p;
        }
        foreach ($placements as $p) {
            $key = (string)($p['target_type'] ?? '') . '/' . (string)($p['target_id'] ?? '');
            // An existing stale link may remain unchanged, but cannot be newly attached.
            $desired[$key] = $oldKeys[$key] ?? $this->target((string)($p['target_type'] ?? ''), (string)($p['target_id'] ?? ''));
        }
        foreach ($old as $p) {
            if (isset($desired[$p['target_type'] . '/' . $p['target_id']])) {
                continue;
            }
            if ($p['target_type'] === 'scope' || $p['url'] === null) {
                $this->connection->delete('material_placements', ['kind' => $kind, 'material_id' => $id, 'target_type' => $p['target_type'], 'target_id' => $p['target_id']]);
            } else {
                $ids = $this->selectionIds($kind, $p['target_type'], $p['target_id']);
                $this->syncTarget($p['target_type'], $p['target_id'], $kind, array_values(array_filter($ids, static fn (int $v): bool => $v !== $id)));
            }
        }
        foreach ($desired as $key => $p) {
            if (!isset($oldKeys[$key])) {
                $this->attach($kind, [$id], $p);
            }
        }
    }

    private function attach(string $kind, array $ids, array $p): void
    {
        $type = (string)$p['target_type'];
        $targetId = (string)$p['target_id'];
        $this->target($type, $targetId);
        if ($type === 'scope') {
            foreach ($ids as $id) {
                $this->connection->executeStatement('INSERT IGNORE INTO material_placements VALUES (?,?,?,?,?)', [$kind, $id, $type, $targetId, 0]);
            }
            return;
        }
        // Serialize concurrent attaches on the stable target row before reading inherited or explicit selections.
        $targetTable = $type === 'product' ? 'products' : 'pages';
        $this->connection->fetchOne("SELECT id FROM {$targetTable} WHERE id=? FOR UPDATE", [$targetId]);
        $current = $this->selectionIds($kind, $type, $targetId);
        $this->syncTarget($type, $targetId, $kind, array_values(array_unique(array_merge($current, $ids))));
    }

    private function context(string $type, string $id): array
    {
        if ($type === 'product') {
            return ['product', (string)$this->connection->fetchOne('SELECT slug FROM products WHERE id=?', [$id])];
        }
        if ($type === 'page') {
            $p = $this->connection->fetchAssociative('SELECT system_key,slug_path FROM pages WHERE id=?', [$id]);
            return $p['system_key'] ? [$p['system_key'], null] : ['page', trim((string)$p['slug_path'], '/')];
        }
        return explode(':', $id, 2) + [1 => null];
    }

    private function selectionIds(string $kind, string $type, string $id): array
    {
        if ($this->connection->fetchOne('SELECT 1 FROM material_target_selections WHERE kind=? AND target_type=? AND target_id=?', [$kind, $type, $id]) || $type === 'scope') {
            return array_map('intval', $this->connection->fetchFirstColumn('SELECT material_id FROM material_placements WHERE kind=? AND target_type=? AND target_id=? ORDER BY sort_order,material_id', [$kind, $type, $id]));
        }
        [$scope,$key] = $this->context($type, $id);
        return $this->inherited($kind, $scope, $key, $type, $id);
    }

    private function inherited(string $kind, string $scope, ?string $key, ?string $type = null, ?string $id = null): array
    {
        $table = $this->table($kind);
        $scopes = ['global', $scope];
        if ($key !== null && $key !== '') {
            $scopes[] = $scope . ':' . $key;
        }
        return array_map('intval', $this->connection->fetchFirstColumn("SELECT DISTINCT m.id,m.sort_order FROM {$table} m JOIN material_placements p ON p.material_id=m.id AND p.kind=? WHERE (p.target_type='scope' AND p.target_id IN (?)) OR (p.target_type=? AND p.target_id=?) ORDER BY m.sort_order,m.id", [$kind, $scopes, $type, $id], [ParameterType::STRING, ArrayParameterType::STRING, ParameterType::STRING, ParameterType::STRING]));
    }
}
