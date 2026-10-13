#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Components\Cacher\Cacher;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/vendor/autoload.php';
$apply = in_array('--apply', $argv, true);
if (array_diff(array_slice($argv, 1), ['--apply', '--help'])) { fwrite(STDERR, "Use --apply or --help\n"); exit(2); }
if (in_array('--help', $argv, true)) { echo "Preview: php bin/apply-content-update.php\nApply once with backup: php bin/apply-content-update.php --apply\nOnly the two 2026-10-11 migrations are executed; no pending unrelated migrations.\n"; exit(0); }
$container = require dirname(__DIR__) . '/config/container.php';
$db = $container->get(Connection::class);
$manifest = json_decode(file_get_contents(dirname(__DIR__) . '/resources/updates/2026-10-11.json'), true, 512, JSON_THROW_ON_ERROR);
$marker = 'content-update:2026-10-11';
if ($db->fetchOne('SELECT id FROM program_locks WHERE id=?', [$marker])) { echo "Main content update already applied.\n"; applyPageCopy($container, $manifest, $apply); applyFaqCopy($container, $manifest, $apply); exit(0); }
// Guard the reviewed SKU identity; never silently apply this manifest to another catalogue.
foreach ($manifest['products'] as $product) {
    if ($db->fetchOne('SELECT name FROM products WHERE id=? AND deleted_at IS NULL', [$product['id']]) !== $product['name']) throw new RuntimeException('Reviewed product identity changed: ' . $product['id']);
}
$counts = ['products' => count($manifest['products']), 'originalDocuments' => count($manifest['certificates']), 'articles' => count($manifest['blog']), 'themes' => count($manifest['themes']), 'hiddenReviews' => count($manifest['hideReviews'])];
echo json_encode(['apply' => $apply] + $counts, JSON_THROW_ON_ERROR) . "\n";
if (!$apply) exit(0);
$backup = [];
foreach (['products','attribute_values','attributes','product_attribute_values','certificates','material_placements','pages','blog_posts'] as $table) $backup[$table] = $db->fetchAllAssociative('SELECT * FROM ' . $table);
$backup['reviews'] = $db->fetchAllAssociative('SELECT id,is_approved FROM reviews');
$backup['bonus_settings'] = $db->fetchAllAssociative("SELECT * FROM site_settings WHERE `key` IN ('order_bonus_percent','order_bonus_spend_limit_percent')");
$backup['program_settings'] = $db->fetchAssociative("SELECT * FROM program_locks WHERE id='settings'");
$backup['migrations'] = $db->fetchAllAssociative('SELECT * FROM migrations');
$folder = dirname(__DIR__) . '/var/backups';
if (!is_dir($folder) && !mkdir($folder, 0700, true)) throw new RuntimeException('Cannot create backup directory.');
$file = $folder . '/content-before-20261011-' . gmdate('YmdHis') . '.json';
if (file_put_contents($file, json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('Cannot save backup.');
chmod($file, 0600);
$factory = $container->get(DependencyFactory::class);
foreach (['App\\Migrations\\Version20261011120000', 'App\\Migrations\\Version20261011121000'] as $version) {
    if ($db->fetchOne('SELECT version FROM migrations WHERE version=?', [$version]) !== false) continue;
    $input = new ArrayInput(['versions' => [$version], '--up' => true]); $input->setInteractive(false);
    $output = new BufferedOutput();
    if ((new ExecuteCommand($factory))->run($input, $output) !== 0) throw new RuntimeException('Target migration failed; inspect migration status.');
}
$protected = $db->fetchAllAssociative('SELECT id,name,price,old_price,image,images,category_id FROM products ORDER BY id');
$db->transactional(function () use ($db, $manifest, $marker, $protected): void {
    $db->fetchOne("SELECT id FROM program_locks WHERE id='finance' FOR UPDATE");
    if ($db->fetchOne('SELECT id FROM program_locks WHERE id=?', [$marker])) throw new RuntimeException('Concurrent update already completed.');
    $now = gmdate('Y-m-d H:i:s');
    foreach ($manifest['themes'] as $theme) {
        $id = $theme['id']; unset($theme['id'], $theme['oldSlug']);
        $db->update('attribute_values', $theme + ['updated_at' => $now], ['id' => $id]);
    }
    // Merge reviewed navigation links without deleting products.
    foreach ([3 => 33, 31 => 4] as $old => $new) {
        $db->executeStatement('INSERT IGNORE INTO product_attribute_values (product_id,attribute_value_id) SELECT product_id,? FROM product_attribute_values WHERE attribute_value_id=?', [$new, $old]);
        $db->delete('product_attribute_values', ['attribute_value_id' => $old]);
    }
    foreach ([3,31,35] as $id) {
        $db->delete('product_attribute_values', ['attribute_value_id' => $id]);
        $db->update('attribute_values', ['deleted_at' => $now, 'is_indexable' => 0, 'updated_at' => $now], ['id' => $id]);
    }
    foreach ($manifest['products'] as $product) { $id=$product['id']; unset($product['id'],$product['name']); $db->update('products', $product + ['updated_at' => $now], ['id' => $id]); }
    foreach ($manifest['blog'] as $article) { $id=$article['id']; unset($article['id']); $db->update('blog_posts', $article + ['updated_at' => $now], ['id' => $id]); }
    foreach ($manifest['hideReviews'] as $id) $db->update('reviews', ['is_approved' => 0, 'updated_at' => $now], ['id' => $id]);
    foreach ($manifest['hideCertificates'] as $id) $db->update('certificates', ['is_active' => 0, 'updated_at' => $now], ['id' => $id]);
    foreach ($manifest['detachCertificates'] as $id) $db->delete('material_placements', ['kind' => 'certificate', 'material_id' => $id]);
    foreach ($manifest['certificates'] as $document) {
        $productId=$document['productId']; unset($document['productId']);
        $id=$db->fetchOne('SELECT id FROM certificates WHERE file_path=?', [$document['file_path']]);
        if ($id === false) { $db->insert('certificates', $document + ['product_id'=>$productId,'is_active'=>1,'sort_order'=>0,'created_at'=>$now]); $id=$db->lastInsertId(); }
        $db->executeStatement('INSERT IGNORE INTO material_placements (kind,material_id,target_type,target_id,sort_order) VALUES (?,?,?,?,?)', ['certificate',(int)$id,'product',(string)$productId,0]);
    }
    foreach (['order_bonus_percent'=>3,'order_bonus_spend_limit_percent'=>100] as $key=>$value) {
        $db->executeStatement('INSERT INTO site_settings (`key`,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$key,json_encode(['value'=>$value],JSON_THROW_ON_ERROR)]);
    }
    $rules=json_decode($db->fetchOne("SELECT payload FROM program_locks WHERE id='settings'") ?: '{}',true,512,JSON_THROW_ON_ERROR);
    if (is_string($rules)) $rules=json_decode($rules,true,512,JSON_THROW_ON_ERROR);
    $rules['buyerBps']=300; $rules['bonusLifetimeMonths']=12;
    $payload=json_encode($rules,JSON_THROW_ON_ERROR);
    $db->executeStatement("INSERT INTO program_locks (id,payload) VALUES ('settings',?) ON DUPLICATE KEY UPDATE payload=VALUES(payload)",[$payload]);
    $db->insert('program_rules',['id'=>bin2hex(random_bytes(16)),'payload'=>$payload,'created_at'=>$now]);
    $db->insert('program_locks',['id'=>$marker,'payload'=>json_encode(['completedAt'=>$now],JSON_THROW_ON_ERROR)]);
    if ($protected !== $db->fetchAllAssociative('SELECT id,name,price,old_price,image,images,category_id FROM products ORDER BY id')) throw new RuntimeException('Protected catalogue values changed unexpectedly.');
});
if ($protected !== $db->fetchAllAssociative('SELECT id,name,price,old_price,image,images,category_id FROM products ORDER BY id')) throw new RuntimeException('Protected catalogue values changed unexpectedly.');
try { foreach (['products','attribute_values','attributes','reviews','blog_posts','pages','certificates'] as $tag) $container->get(Cacher::class)->deleteTag($tag); }
catch (Throwable) { fwrite(STDERR, "Data applied; cache service unreachable. Cached content expires according to its configured TTL.\n"); }
echo "Applied and verified. Backup: " . $file . "\n";

applyPageCopy($container, $manifest, true);
applyFaqCopy($container, $manifest, true);

/** Separate idempotent phase: keeps the reviewed catalogue update immutable after application. */
function applyPageCopy($container, array $manifest, bool $apply): void
{
    $db = $container->get(Connection::class);
    $marker = 'content-copy:2026-10-12';
    if ($db->fetchOne('SELECT id FROM program_locks WHERE id=?', [$marker])) { echo "Page copy update already applied.\n"; return; }
    echo "Page copy update: " . count($manifest['pages']) . "; apply=" . ($apply ? 'yes' : 'no') . "\n";
    if (!$apply) return;
    foreach ($manifest['pages'] as $page) {
        $expected = $page['id'] === 22 ? 'dostavka' : 'oplata';
        if ($db->fetchOne('SELECT slug_path FROM pages WHERE id=?', [$page['id']]) !== $expected) throw new RuntimeException('Page identity changed.');
    }
    $folder = dirname(__DIR__) . '/var/backups';
    if (!is_dir($folder)) mkdir($folder, 0700, true);
    $file = $folder . '/page-copy-before-' . gmdate('YmdHis') . '.json';
    $backup = ['pages'=>$db->fetchAllAssociative('SELECT * FROM pages WHERE id IN (22,23)'), 'address'=>$db->fetchAssociative("SELECT * FROM site_settings WHERE `key`='site_address_street'")];
    if (file_put_contents($file, json_encode($backup, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('Cannot save copy backup.');
    chmod($file, 0600);
    $db->transactional(function () use ($db, $manifest, $marker): void {
        $db->fetchOne("SELECT id FROM program_locks WHERE id='finance' FOR UPDATE");
        if ($db->fetchOne('SELECT id FROM program_locks WHERE id=?', [$marker])) throw new RuntimeException('Concurrent page copy update.');
        foreach ($manifest['pages'] as $page) { $id=$page['id'];unset($page['id']);$db->update('pages',$page+['updated_at'=>gmdate('Y-m-d H:i:s')],['id'=>$id]); }
        $db->executeStatement("INSERT INTO site_settings (`key`,value) VALUES ('site_address_street',?) ON DUPLICATE KEY UPDATE value=VALUES(value)",[json_encode(['value'=>'особая экономическая зона, микрорайон Академгородок, проспект Развития, д. 3Е'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
        $db->insert('program_locks',['id'=>$marker,'payload'=>'{}']);
    });
    foreach (['pages','site_settings'] as $tag) $container->get(Cacher::class)->deleteTag($tag);
    echo "Page copy update applied. Backup: " . $file . "\n";
}

/** Align remaining FAQ copy without replaying the applied product update. */
function applyFaqCopy($container, array $manifest, bool $apply): void
{
    $db = $container->get(Connection::class);
    $marker = 'content-faq:2026-10-12';
    if ($db->fetchOne('SELECT id FROM program_locks WHERE id=?', [$marker])) { echo "FAQ copy update already applied.\n"; return; }
    echo "FAQ copy update: " . count($manifest['faq']) . "; apply=" . ($apply ? 'yes' : 'no') . "\n";
    $backup = [];
    foreach ($manifest['faq'] as $faq) {
        $row = $db->fetchAssociative('SELECT * FROM faq_items WHERE id=?', [$faq['id']]);
        if (!$row || $row['question'] !== $faq['question'] || $row['page_scope'] !== $faq['page_scope'] || $row['page_id'] !== $faq['page_id'] || $row['answer'] !== $faq['previousAnswer']) throw new RuntimeException('Reviewed FAQ changed: ' . $faq['id']);
        $backup[] = $row;
    }
    if (!$apply) return;
    $folder = dirname(__DIR__) . '/var/backups';
    if (!is_dir($folder) && !mkdir($folder, 0700, true)) throw new RuntimeException('Cannot create backup directory.');
    $file = $folder . '/faq-copy-before-' . gmdate('YmdHis') . '.json';
    if (file_put_contents($file, json_encode($backup, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('Cannot save FAQ backup.');
    chmod($file, 0600);
    $db->transactional(function () use ($db, $manifest, $marker): void {
        $db->fetchOne("SELECT id FROM program_locks WHERE id='finance' FOR UPDATE");
        if ($db->fetchOne('SELECT id FROM program_locks WHERE id=?', [$marker])) throw new RuntimeException('Concurrent FAQ update.');
        foreach ($manifest['faq'] as $faq) {
            $affected = $db->executeStatement('UPDATE faq_items SET answer=?,updated_at=? WHERE id=? AND answer=?', [$faq['answer'],gmdate('Y-m-d H:i:s'),$faq['id'],$faq['previousAnswer']]);
            if ($affected !== 1) throw new RuntimeException('FAQ changed during update: ' . $faq['id']);
        }
        $db->insert('program_locks', ['id'=>$marker,'payload'=>'{}']);
    });
    foreach (['faq_items','products','pages'] as $tag) $container->get(Cacher::class)->deleteTag($tag);
    echo "FAQ copy update applied. Backup: " . $file . "\n";
}
