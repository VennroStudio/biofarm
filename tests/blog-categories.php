<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\String\SlugGenerator;
use App\Modules\Blog\Service\BlogCategoryService;
use Doctrine\DBAL\DriverManager;

function expect(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function rejected(callable $action, int $status): void {
    try { $action(); } catch (DomainExceptionModule $e) { expect($e->getStatus() === $status, 'Wrong error status'); return; }
    throw new RuntimeException('Expected rejection');
}
$db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
$db->executeStatement('CREATE TABLE blog_categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, slug TEXT UNIQUE, sort_order INTEGER, is_indexable INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
$db->executeStatement('CREATE TABLE blog_posts (id INTEGER PRIMARY KEY, category_id TEXT, deleted_at TEXT)');
$cacher = new class implements Cacher {
    public function get(string $key): array|bool|float|int|object|string|null
    {
        return null;
    }

    public function set(string $key, array|bool|float|int|object|string|null $value, ?int $ttl = null): bool
    {
        return true;
    }

    public function setTagged(string $key, array|bool|float|int|object|string|null $value, int $ttl, array $tags): bool
    {
        return true;
    }

    public function delete(string $key): void {}

    public function deleteTag(string $tag): void {}

    public function expire(string $key, int $ttl): void {}

    public function mGet(array $keys): array
    {
        return [];
    }

    public function zAdd(string $key, float $score, float|int|string $value): void {}

    public function zRangeByScore(string $key, int $min, int $max, ?int $offset = null, ?int $count = null): array
    {
        return [];
    }

    public function zRevRangeByScore(string $key, int $max, int $min, ?int $offset = null, ?int $count = null): array
    {
        return [];
    }

    public function increase(string $key, int $value): void {}

    public function decrease(string $key, int $value): void {}

    public function sAdd(string $key, string $value): void {}

    public function sMembers(string $key): array
    {
        return [];
    }
};
$service = new BlogCategoryService($db, $cacher, new SlugGenerator());
$id = $service->save(null, ['name' => 'Здоровье', 'sort_order' => 20]);
$other = $service->save(null, ['name' => 'Советы', 'sort_order' => 10]);
expect($service->all()[0]['id'] === $other, 'Categories must follow display order');
$service->assertAvailable('zdorove');
$db->insert('blog_posts', ['id' => 1, 'category_id' => 'zdorove']);
expect($service->all()[1]['posts_count'] === 1, 'Count linked posts');
rejected(fn() => $service->delete($id), 409);
$service->save($id, ['name' => 'О здоровье', 'slug' => 'health', 'sort_order' => 0]);
expect($db->fetchOne('SELECT category_id FROM blog_posts WHERE id = 1') === 'health', 'Slug edit preserves post association');
rejected(fn() => $service->assertAvailable('zdorove'), 422);
rejected(fn() => $service->save(null, ['name' => 'О здоровье']), 422);
rejected(fn() => $service->save(null, ['name' => 'Другое', 'slug' => 'health']), 422);
rejected(fn() => $service->save($id, ['name' => 'О здоровье', 'slug' => 'sovety']), 422);
expect($db->fetchOne('SELECT category_id FROM blog_posts WHERE id = 1') === 'health', 'Failed rename must preserve association');
foreach ([['name' => ''], ['name' => 'Все'], ['name' => 'Тест', 'sort_order' => -1], ['name' => 'Тест', 'sort_order' => '1.5']] as $invalid) rejected(fn() => $service->save(null, $invalid), 422);
$service->delete($other);
expect(count($service->all()) === 1, 'Deleted category hidden');
rejected(fn() => $service->assertAvailable('sovety'), 422);
rejected(fn() => $service->save(999, ['name' => 'Нет']), 404);
echo "PASS: categories CRUD, order, validation, protected deletion, slug associations\n";
