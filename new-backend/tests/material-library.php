<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Content\Service\MaterialLibrary;
use Doctrine\DBAL\Connection;

if (getenv('APP_ENV') !== 'dev') {
    throw new RuntimeException('Development database only; fixtures are rolled back.');
}
$c = require dirname(__DIR__) . '/config/container.php';
$db = $c->get(Connection::class);
$library = $c->get(MaterialLibrary::class);
$checks = 0;
function check(bool $ok, string $why): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($why);
    } ++$checks;
}
function rejected(callable $run): void
{
    try {
        $run();
    } catch (DomainExceptionModule $e) {
        check($e->getStatus() === 422, 'Expected validation error');
        return;
    } throw new RuntimeException('Expected rejection');
}
$db->beginTransaction();
try {
    $products = $db->fetchAllAssociative('SELECT id,slug FROM products WHERE deleted_at IS NULL ORDER BY id LIMIT 2');
    check(count($products) === 2, 'Two existing targets required');
    $one = (string)$products[0]['id'];
    $two = (string)$products[1]['id'];
    $category = $library->saveCategory('certificate', null, ['name' => '[TEST rollback] Material category ' . bin2hex(random_bytes(4))]);
    $a = $library->save('certificate', null, ['title' => '[TEST rollback] Shared certificate', 'file_path' => '/uploads/test.pdf', 'category_id' => $category]);
    $b = $library->save('certificate', null, ['title' => '[TEST rollback] Ordered certificate', 'file_path' => '/uploads/test.pdf']);
    $targets = [['target_type' => 'product', 'target_id' => $one], ['target_type' => 'product', 'target_id' => $two]];
    $library->bulkAttach('certificate', [$a], $targets);
    $library->bulkAttach('certificate', [$a], $targets);
    check($library->get('certificate', $a)['usage_count'] === 2, 'Shared additive idempotent attach');
    rejected(static fn () => $library->delete('certificate', $a));
    rejected(static fn () => $library->deleteCategory('certificate', $category));
    $library->syncTarget('product', $one, 'certificate', [$b, $a]);
    check($library->selections('product', $one)['certificate_ids'] === [$b, $a], 'Saved selection order');
    check(array_map('intval', array_column($library->publicItems('certificate', 'product', $one), 'id')) === [$b, $a], 'Public order');
    $library->syncTarget('product', $one, 'certificate', []);
    check(in_array($a, $library->selections('product', $two)['certificate_ids'], true), 'Scoped detach preserves other product');
    check($library->publicItems('certificate', 'product', $one) === [], 'Empty certificate selection');
    $library->save('certificate', $a, ['is_active' => false]);
    check(!in_array($a, array_map('intval', array_column($library->publicItems('certificate', 'product', $two), 'id')), true), 'Disabled hidden publicly');
    check(in_array($a, $library->selections('product', $two)['certificate_ids'], true), 'Disabled placement preserved');
    $faq = $library->save('faq', null, ['question' => '[TEST rollback] Global question', 'answer' => 'Answer']);
    $db->insert('material_placements', ['kind' => 'faq', 'material_id' => $faq, 'target_type' => 'scope', 'target_id' => 'global', 'sort_order' => -1]);
    $db->delete('material_target_selections', ['kind' => 'faq', 'target_type' => 'product', 'target_id' => $one]);
    check(in_array($faq, $library->selections('product', $one)['faq_ids'], true), 'Inherited FAQ selection');
    $library->syncTarget('product', $one, 'faq', []);
    check($library->publicItems('faq', 'product', $products[0]['slug']) === [], 'Explicit empty suppresses inheritance');
    $library->syncTarget('product', $one, 'faq', [$faq]);
    $db->update('products', ['slug' => 'test-rollback-material-slug'], ['id' => (int)$one]);
    check((int)$library->publicItems('faq', 'product', 'test-rollback-material-slug')[0]['id'] === $faq, 'Stable placement survives slug edit');
    check($library->publicItems('faq', 'page', '/unknown-test-rollback') === [], 'Unknown page receives no global FAQ');
    check($library->listing('certificate', ['category_id' => $category])['total'] === 1, 'Category filter');
    check(in_array($b, array_column($library->listing('certificate', ['ids' => (string)$b, 'category_id' => 0])['items'], 'id'), true), 'Uncategorized filter');
    check($library->listing('certificate', ['target_type' => 'product', 'ids' => (string)$a])['total'] === 1, 'Target type filter');
    $usage = $library->get('certificate', $a)['placements'];
    $library->save('certificate', $a, ['placements' => []]);
    check($library->get('certificate', $a)['usage_count'] === 0, 'Usage modal removes placement');
    $library->delete('certificate', $a);
    $library->deleteCategory('certificate', $category);
    $before = $library->selections('product', $one);
    rejected(static fn () => $library->bulkAttach('certificate', [$b], [['target_type' => 'product', 'target_id' => $one], ['target_type' => 'product', 'target_id' => '999999999']]));
    check($before === $library->selections('product', $one), 'Bulk invalid target rolls back whole operation');
    $library->syncTarget('product', $two, 'certificate', [$b]);
    $db->update('products', ['deleted_at' => gmdate('Y-m-d H:i:s')], ['id' => (int)$two]);
    $stale = $library->get('certificate', $b)['placements'];
    $library->save('certificate', $b, ['title' => '[TEST rollback] Edited with stale target', 'placements' => $stale]);
    check($library->get('certificate', $b)['usage_count'] === 1, 'Unchanged stale target remains editable');
    $library->save('certificate', $b, ['placements' => []]);
    check($library->get('certificate', $b)['usage_count'] === 0, 'Deleted target placement removable');
    check($library->listing('faq', ['target_type' => 'product', 'target_id' => ''])['page'] === 1, 'Empty target ID type-only filter');
    $page = $db->fetchAssociative("SELECT id,slug_path FROM pages WHERE page_type='custom' AND deleted_at IS NULL LIMIT 1");
    if ($page) {
        $library->syncTarget('page', (string)$page['id'], 'faq', [$faq]);
        $db->update('pages', ['is_published' => 0], ['id' => $page['id']]);
        check($library->publicItems('faq', 'page', $page['slug_path']) === [], 'Unpublished custom page hidden');
    }
    echo "Material library: {$checks} checks passed; fixtures rolled back.\n";
} finally {
    $db->rollBack();
}
