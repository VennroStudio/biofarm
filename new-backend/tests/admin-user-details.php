<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Program\Service\ProgramService;
use App\Modules\User\Service\AdminUserDetails;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
$config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__) . '/src/Modules/Program/Entity'], true);
$config->enableNativeLazyObjects(true);
$config->setNamingStrategy(new UnderscoreNamingStrategy());
$em = new EntityManager($db, $config);
new SchemaTool($em)->createSchema($em->getMetadataFactory()->getAllMetadata());
$db->executeStatement('CREATE TABLE users (id INTEGER PRIMARY KEY,first_name TEXT,last_name TEXT,email TEXT,status INTEGER DEFAULT 1,deleted_at TEXT,created_at TEXT)');
$db->executeStatement('CREATE TABLE user_profiles (user_id INTEGER PRIMARY KEY,bonus_balance INTEGER,is_partner INTEGER,referred_by_user_id INTEGER,referral_code TEXT)');
$db->executeStatement('CREATE TABLE orders (id TEXT PRIMARY KEY,user_id INTEGER,total INTEGER,payment_status TEXT,status TEXT,created_at TEXT,paid_at TEXT)');
$db->executeStatement('CREATE TABLE user_addresses (id INTEGER PRIMARY KEY,user_id INTEGER,label TEXT,name TEXT,phone TEXT,city TEXT,address TEXT,postal_code TEXT,is_default INTEGER,deleted_at TEXT)');
for ($id = 1; $id <= 5; ++$id) {
    $db->insert('users', ['id'=>$id,'first_name'=>'User','last_name'=>(string)$id,'created_at'=>'2026-09-18 10:00:00']);
    $db->insert('user_profiles', ['user_id'=>$id,'is_partner'=> $id === 1 ? 1 : 0,'bonus_balance'=>0,'referral_code'=>'ref-'.$id,'referred_by_user_id'=>$id >= 2 ? 1 : null]);
}
$db->executeStatement('CREATE TABLE site_settings (`key` TEXT PRIMARY KEY,value TEXT)');
foreach (['cart_enabled','referral_enabled','withdrawals_enabled'] as $flag) $db->insert('site_settings',['key'=>$flag,'value'=>'true']);
$program = new ProgramService($db);
$details = new AdminUserDetails($db, $program);
$program->joinTeam(2, $program->teamInvite(1)['code'], true);
$db->update('user_profiles',['is_partner'=>1],['user_id'=>4]);
$db->update('users',['deleted_at'=>'2026-09-18'],['id'=>5]);
check(array_map('intval',array_column($details->get(1,'team')['items'],'id')) === [2], 'Only explicit member in team');
check(array_map('intval',array_column($details->get(1,'referrals')['items'],'id')) === [3], 'Members, partners and deleted users excluded from referrals');
for ($i=1; $i<=21; ++$i) $db->insert('orders',['id'=>'mine-'.$i,'user_id'=>3,'total'=>1000,'payment_status'=>$i===1?'completed':'pending','created_at'=>'2026-09-18 10:00:00']);
$db->insert('orders',['id'=>'other','user_id'=>2,'total'=>9999,'payment_status'=>'completed']);
$db->insert('orders',['id'=>'guest','user_id'=>null,'total'=>9999,'payment_status'=>'completed']);
$first=$details->get(3,'orders'); $second=$details->get(3,'orders',2);
check($first['count']===21 && count($first['items'])===20 && count($second['items'])===1,'Pagination scoped to user');
check(count(array_intersect(array_column($first['items'],'id'),array_column($second['items'],'id')))===0,'Stable pagination without duplicates');
$ref=$details->get(1,'referrals')['items'][0];
check((int)$ref['orders_count']===21 && (int)$ref['paid_total']===1000,'Only paid personal orders included in total');
$program->adjust(1,'commission',15000,'fixture',1);
$program->adjust(1,'shopping',700,'fixture',1);
$program->adjust(2,'commission',99900,'fixture',1);
$commission=$details->get(1,'commission');
check($commission['balances']['commission']['availableMinor']===15000 && $commission['balances']['shopping']['availableMinor']===700,'Balances separated, in kopecks');
check(count($commission['items'])===1 && (int)$commission['items'][0]['amount_minor']===15000,'Commission rows isolated from shopping and other users');
$program->requestWithdrawal(1,'100',['recipient'=>'User 1','bank'=>'Test','account'=>'fixture']);
check(count($details->get(1,'withdrawals')['items'])===1 && count($details->get(2,'withdrawals')['items'])===0,'Withdrawals scoped');
check($details->get(1,'commission')['balances']['commission']['reservedMinor']===10000,'Payout reservation displayed');
$db->insert('user_addresses',['id'=>1,'user_id'=>1,'label'=>'Home','is_default'=>1]);
$db->insert('user_addresses',['id'=>2,'user_id'=>2,'label'=>'Other','is_default'=>1]);
check(array_column($details->get(1)['items'],'label')===['Home'],'Addresses scoped');
foreach ([[1,'invalid'],[999,'profile'],[5,'profile']] as [$id,$section]) {
    try { $details->get($id,$section); throw new RuntimeException('Expected rejection'); }
    catch (DomainExceptionModule $e) { check(in_array($e->getStatus(),[404,422],true),'Expected 404 or 422'); }
}
echo "Admin user details: OK\n";
