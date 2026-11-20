<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Modules\Product\Service\CatalogPackagingUpdate;
use Doctrine\DBAL\DriverManager;

$db = DriverManager::getConnection(['driver'=>'pdo_mysql','host'=>'biofarm-catalog-test-20261028','dbname'=>'catalog_fixture','user'=>'root','password'=>'catalog-fixture-only','charset'=>'utf8mb4']);
if ($db->fetchOne('SELECT DATABASE()') !== 'catalog_fixture') throw new RuntimeException('Isolated fixture only');
$manifest=json_decode(file_get_contents(dirname(__DIR__).'/resources/updates/2026-10-28-catalog.json'),true,512,JSON_THROW_ON_ERROR);
function check(bool $ok,string $why): void { if (!$ok) throw new RuntimeException($why); }
function fingerprint($db): string { $data=[];foreach(['products','product_groups','product_group_items','product_images','product_attribute_values','product_blog_posts','attribute_values','faq_items','material_placements','material_target_selections','program_locks'] as $t) {$r=$db->fetchAllAssociative('SELECT * FROM '.$t);sort($r);$data[$t]=$r;} return hash('sha256',serialize($data)); }
$before=fingerprint($db);
$untouched=$db->fetchAllAssociative('SELECT * FROM products WHERE id IN (11,12,36,37) ORDER BY id');
$run=new CatalogPackagingUpdate($db,dirname(__DIR__).'/public',$manifest);
$run->preview();check(fingerprint($db)===$before,'Preview must not write');
$bad=$manifest;$bad['families'][0]['expected_name']='Wrong product';
try {(new CatalogPackagingUpdate($db,dirname(__DIR__).'/public',$bad))->apply();throw new RuntimeException('Wrong identity accepted');}catch(InvalidArgumentException $e) {}
check(fingerprint($db)===$before,'Identity rejection must be atomic');
// A bad later row must reject the entire import, including earlier families.
$bad=$manifest;$bad['families'][26]['variants'][3]['photo']['sha256']=str_repeat('0',64);
try {(new CatalogPackagingUpdate($db,dirname(__DIR__).'/public',$bad))->apply();throw new RuntimeException('Wrong photo accepted');}catch(InvalidArgumentException $e) {}
check(fingerprint($db)===$before,'Photo rejection must not leave partial data');
$db->executeStatement("CREATE TRIGGER catalog_fixture_failure BEFORE INSERT ON product_images FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture rollback'");
try {$run->apply();throw new RuntimeException('Database error accepted');}catch(\Doctrine\DBAL\Exception $e) {}finally {$db->executeStatement('DROP TRIGGER catalog_fixture_failure');}
check(fingerprint($db)===$before,'A database error after writes must roll back all rows and marker');
$result=$run->apply();
check((int)$db->fetchOne('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL')===95,'Expected 95 nondeleted product cards');
check((int)$db->fetchOne('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL AND is_active=1')===74,'Expected 74 published cards');
check((int)$db->fetchOne('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL AND is_active=0')===21,'Expected 21 drafts');
check($db->fetchAllAssociative('SELECT * FROM products WHERE id IN (11,12,36,37) ORDER BY id')===$untouched,'Ambiguous and deleted products remain byte-identical');
foreach($manifest['families'] as $family) {
 $g=[];
 foreach($family['variants'] as $variant) {
  $p=$db->fetchAssociative('SELECT * FROM products WHERE slug=?',[$variant['slug']]);
  check($p!==false && (int)$p['price']===$variant['price'],'Exact retail price / distinct card: '.$variant['slug']);
  check((bool)$p['is_active']===$variant['active'],'Publication rule: '.$variant['slug']);
  check($p['weight']===$variant['weight'],'Pack label: '.$variant['slug']);
  check($p['name']===$family['name'],'Keep approved product name');
  if(in_array($family['unit'],['капсул','мл'],true)) {
   $attribute=$family['unit']==='капсул' ? 18 : 13;
   $values=$db->fetchFirstColumn('SELECT av.name FROM product_attribute_values pav JOIN attribute_values av ON av.id=pav.attribute_value_id WHERE pav.product_id=? AND av.attribute_id=?',[$p['id'],$attribute]);
   check($values===[$variant['quantity'].($family['unit']==='капсул' ? ' шт.' : ' мл')],'Exact pack characteristic: '.$variant['slug']);
  }
  $g[]=(int)$db->fetchOne('SELECT group_id FROM product_group_items WHERE product_id=?',[$p['id']]);
  if(isset($variant['photo'])) check($p['image']===$variant['photo']['path'],'Exact matching mockup');
  if($variant['existing_id']===null && $family['base_id']!==null) {
   foreach(['product_attribute_values','product_blog_posts'] as $t) check((int)$db->fetchOne('SELECT COUNT(*) FROM '.$t.' WHERE product_id=?',[$p['id']])===(int)$db->fetchOne('SELECT COUNT(*) FROM '.$t.' WHERE product_id=?',[$family['base_id']]),'Shared attributes/articles copied');
   check($p['wb_link']===null && $p['ozon_link']===null,'Never copy another pack marketplace link');
   if($family['unit']==='капсул' && $variant['quantity']!==$family['old_quantity']) check(!preg_match('/(?<!\d)'.$family['old_quantity'].'\s*капсул/u',$p['description'].' '.$p['features'].' '.$p['seo_title']),'No obsolete pack in cloned description');
  }
 }
 check(count(array_unique($g))===1 && $g[0]>0,'One group per product family');
 check((int)$db->fetchOne('SELECT COUNT(*) FROM product_group_items WHERE group_id=?',[$g[0]])===count($family['variants']),'No unrelated products in group');
}
check($db->fetchOne("SELECT payload FROM program_locks WHERE id='finance'")==='{}','Finance lock unchanged');
check(str_contains((string)$db->fetchOne('SELECT description FROM products WHERE slug=?',['pantogematogen-30-kapsul']),'30 желатиновых капсул'),'Adjective-separated pack count must match the label');
check(str_contains((string)$db->fetchOne('SELECT seo_description FROM products WHERE slug=?',['rodiola-rozovaya-120-kapsul']),'120 желатиновых капсул'),'SEO must match the label too');
$after=fingerprint($db);check($run->apply()['already_applied']===true,'Second run detected');check(fingerprint($db)===$after,'Second run is a no-op');
echo "Catalogue packaging: preview, identity/photo rejection, 95 cards, 74 active, 21 hidden, 27 groups, prices, photos, links, content, skipped products and idempotency passed.\n";
