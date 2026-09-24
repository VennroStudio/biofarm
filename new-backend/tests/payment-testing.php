<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Components\Exception\DomainExceptionModule;
use App\Components\Setting\SiteSettings;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\Payment\Service\YooKassaGateway;
use App\Modules\Program\Service\ProgramService;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use GuzzleHttp\Client;

function check(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}
$db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
$config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__) . '/src/Modules/Program/Entity', dirname(__DIR__) . '/src/Modules/Payment/Entity'], true);
$config->enableNativeLazyObjects(true);
$config->setNamingStrategy(new UnderscoreNamingStrategy());
$em = new EntityManager($db, $config);
new SchemaTool($em)->createSchema($em->getMetadataFactory()->getAllMetadata());
$db->executeStatement('CREATE TABLE site_settings (`key` TEXT PRIMARY KEY,value TEXT)');
$db->executeStatement('CREATE TABLE users (id INTEGER PRIMARY KEY,first_name TEXT,last_name TEXT,email TEXT,status INTEGER DEFAULT 1,deleted_at TEXT)');
$db->executeStatement('CREATE TABLE user_profiles (user_id INTEGER PRIMARY KEY,bonus_balance INTEGER,is_partner INTEGER,referred_by_user_id INTEGER,referral_code TEXT)');
$db->executeStatement('CREATE TABLE orders (id TEXT PRIMARY KEY,user_id INTEGER,total INTEGER,discount_amount INTEGER,bonus_used INTEGER,delivery_cost INTEGER,payment_status TEXT,referred_by TEXT,promo_code TEXT,status TEXT,paid_at TEXT,updated_at TEXT,shipping_address TEXT)');
$db->executeStatement('CREATE TABLE order_items (id INTEGER PRIMARY KEY AUTOINCREMENT,order_id TEXT,product_id INTEGER,product_name TEXT,price INTEGER,quantity INTEGER)');
foreach (['cart_enabled','referral_enabled','order_bonus_enabled'] as $key) $db->insert('site_settings', ['key'=>$key,'value'=>'true']);
$db->insert('users',['id'=>1,'first_name'=>'Partner','email'=>'partner@example.test']);
$db->insert('user_profiles',['user_id'=>1,'bonus_balance'=>0,'is_partner'=>1,'referral_code'=>'partner']);
$db->insert('users',['id'=>2,'first_name'=>'Buyer','email'=>'buyer@example.test']);
$db->insert('user_profiles',['user_id'=>2,'bonus_balance'=>0,'is_partner'=>0,'referred_by_user_id'=>1,'referral_code'=>'buyer']);
$networkCalls = 0;
$client = new Client(['handler'=>static function () use (&$networkCalls) { ++$networkCalls; throw new RuntimeException('Unexpected provider call'); }]);
$program = new ProgramService($db);
$payments = new PaymentService($db, new YooKassaGateway($client, []), $program);
$fixture = static function (string $id, ?int $buyer = null) use ($db, $program): int {
    $db->insert('orders',['id'=>$id,'user_id'=>$buyer,'status'=>'pending','payment_status'=>'pending','total'=>10350,'discount_amount'=>0,'bonus_used'=>0,'delivery_cost'=>350,'referred_by'=>'partner','shipping_address'=>'{}']);
    $db->insert('order_items',['order_id'=>$id,'product_id'=>1,'product_name'=>'Fixture','quantity'=>1,'price'=>10000]);
    $item=(int)$db->lastInsertId();$program->captureOrder($id);return $item;
};
check(SiteSettings::defaults()['testing_enabled'] === false, 'Testing must default to disabled');
check(in_array('testing_enabled', SiteSettings::adminWritableKeys(), true), 'Admin can save testing setting');
$fixture('disabled');
check(!$payments->completeCheckoutForTesting('disabled'), 'Disabled mode does not settle');
check($db->fetchOne("SELECT payment_status FROM orders WHERE id='disabled'") === 'pending', 'Normal order stays pending');
$db->insert('site_settings',['key'=>'testing_enabled','value'=>'{"value":true}']);
foreach ([null,2] as $buyer) {
    $id=$buyer===null?'guest':'member';$fixture($id,$buyer);
    check($payments->completeCheckoutForTesting($id), 'Testing completes checkout');
    $state=$payments->status($id);
    check($state['orderPaymentStatus']==='completed' && $state['status']==='succeeded' && $state['confirmationUrl']===null, 'Same success state without redirect');
    check($db->fetchOne('SELECT paid_at FROM orders WHERE id=?',[$id])!==null, 'Payment timestamp recorded');
    check((int)$db->fetchOne("SELECT SUM(amount_minor) FROM program_ledger WHERE order_id=? AND wallet='commission'",[$id])===10000, 'Partner receives 1% of goods, delivery excluded');
    $count=(int)$db->fetchOne('SELECT COUNT(*) FROM program_ledger WHERE order_id=?',[$id]);
    $payments->completeCheckoutForTesting($id);$payments->start($id);
    check((int)$db->fetchOne('SELECT COUNT(*) FROM program_ledger WHERE order_id=?',[$id])===$count, 'Retries do not duplicate rewards');
    check((int)$db->fetchOne('SELECT COUNT(*) FROM payment_operations WHERE order_id=?',[$id])===1, 'Only one payment');
}
check((int)$db->fetchOne("SELECT SUM(amount_minor) FROM program_ledger WHERE order_id='member' AND wallet='shopping'")===10000, 'Registered buyer receives normal shopping bonus');
$fixture('cancelled');$db->update('orders',['status'=>'cancelled'],['id'=>'cancelled']);
try {$payments->completeCheckoutForTesting('cancelled');throw new RuntimeException('Cancelled order must reject');} catch (DomainExceptionModule) {}
$fixture('in-flight');
$db->insert('payment_operations',['id'=>'live-op','order_id'=>'in-flight','kind'=>'payment','status'=>'pending','amount_minor'=>1035000,'provider_id'=>'live-provider','request_payload'=>'{}','created_at'=>gmdate('Y-m-d H:i:s'),'updated_at'=>gmdate('Y-m-d H:i:s')]);
try {$payments->completeCheckoutForTesting('in-flight');throw new RuntimeException('In-flight real payment must reject');} catch (DomainExceptionModule) {}
$db->delete('payment_operations',['id'=>'live-op']);
$db->update('site_settings',['value'=>'false'],['key'=>'testing_enabled']);
check($payments->start('guest')['orderPaymentStatus']==='completed', 'Existing simulated payment remains completed when flag disabled');
$fixture('normal-again');check(!$payments->completeCheckoutForTesting('normal-again'), 'Disabling restores normal checkout');
try {$payments->start('normal-again');throw new RuntimeException('Real payment must still require configuration');} catch (DomainExceptionModule) {}
$program->deliverOrder('guest');
check($payments->settlementReceipt('guest')['status']==='not_required','No fiscal receipt for simulated payment');
$item=(int)$db->fetchOne("SELECT id FROM order_items WHERE order_id='guest'");
$refund=$payments->requestRefund('guest','refund-fixture-0001',[['itemId'=>$item,'quantity'=>1]],true);
check($refund['status']==='succeeded','Refund does not contact provider for simulated payment');
check($db->fetchOne("SELECT payment_status FROM orders WHERE id='guest'")==='refunded','Normal refund status is used');
$payments->requestRefund('guest','refund-fixture-0001',[['itemId'=>$item,'quantity'=>1]],true);
$payments->reconcile();
check($networkCalls===0,'No provider calls in the simulated lifecycle');
echo "Payment testing: disabled/enabled, guest/buyer rewards, retries, cancellation, real-payment isolation, delivery and refund passed.\n";
