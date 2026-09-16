<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Modules\Program\Service\ProgramMath;
use App\Modules\Program\Service\ProgramService;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

function check(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
function fails(callable $fn, string $message): void
{
    try {
        $fn();
    } catch (DomainException) {
        return;
    }throw new RuntimeException($message);
}
$db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
$config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__) . '/src/Modules/Program/Entity'], true);
$config->enableNativeLazyObjects(true);
$config->setNamingStrategy(new UnderscoreNamingStrategy());
$em = new EntityManager($db, $config);
new SchemaTool($em)->createSchema($em->getMetadataFactory()->getAllMetadata());
$db->executeStatement('CREATE TABLE site_settings (`key` TEXT PRIMARY KEY, value TEXT)');
foreach (['cart_enabled', 'referral_enabled', 'order_bonus_enabled', 'withdrawals_enabled'] as $flag) {
    $db->insert('site_settings', ['key' => $flag, 'value' => 'true']);
}
$db->executeStatement('CREATE TABLE promo_code_redemptions (order_id TEXT, promo_code_id INTEGER)');
$db->executeStatement('CREATE TABLE payment_operations (id TEXT PRIMARY KEY, order_id TEXT, kind TEXT, status TEXT)');
$db->executeStatement('CREATE TABLE users (id INTEGER PRIMARY KEY,first_name TEXT,last_name TEXT)');
$db->executeStatement('CREATE TABLE user_profiles (user_id INTEGER PRIMARY KEY,bonus_balance INTEGER,is_partner INTEGER,referred_by_user_id INTEGER,referral_code TEXT)');
$db->executeStatement('CREATE TABLE orders (id TEXT PRIMARY KEY,user_id INTEGER,total INTEGER,discount_amount INTEGER,bonus_used INTEGER,delivery_cost INTEGER,payment_status TEXT,referred_by TEXT,promo_code TEXT,status TEXT,updated_at TEXT)');
$db->executeStatement('CREATE TABLE order_items (id INTEGER PRIMARY KEY AUTOINCREMENT,order_id TEXT,product_id INTEGER,product_name TEXT,price INTEGER,quantity INTEGER)');
$now = new DateTimeImmutable('2026-09-17 12:00:00', new DateTimeZone('UTC'));
$p = new ProgramService($db, static function () use (&$now) {return $now; });
for ($i = 1; $i <= 7; ++$i) {
    $db->insert('users', ['id' => $i, 'first_name' => 'Fixture', 'last_name' => (string)$i]);
    $db->insert('user_profiles', ['user_id' => $i, 'bonus_balance' => $i === 7 ? 1000 : 0, 'is_partner' => $i === 1 ? 1 : 0, 'referred_by_user_id' => $i === 1 ? null : $i - 1, 'referral_code' => 'bf-' . $i]);
}
function order($db, $p, string $id, int $buyer = 7, int $bonus = 0, int $price = 10000, int $quantity = 1, int $discount = 0): int
{
    $db->insert('orders', ['id' => $id, 'user_id' => $buyer, 'total' => $price * $quantity - $discount - $bonus + 350, 'discount_amount' => $discount, 'bonus_used' => $bonus, 'delivery_cost' => 350, 'payment_status' => 'pending', 'referred_by' => 'bf-1']);
    $db->insert('order_items', ['order_id' => $id, 'product_id' => 1, 'product_name' => 'Test product', 'price' => $price, 'quantity' => $quantity]);
    $item = (int)$db->lastInsertId();
    $p->captureOrder($id);
    return $item;
}
function paid($db, $p, string $id): void
{
    $db->update('orders', ['payment_status' => 'completed'], ['id' => $id]);
    $p->settleOrder($id);
}
check(ProgramMath::allocate(5, [1 => 3, 2 => 3, 3 => 3]) === [1 => 2, 2 => 2, 3 => 1], 'allocation');
fails(static fn () => ProgramMath::rules(['buyerBps' => 500]), 'budget cap');
check(ProgramMath::minor('0.25') === 25, 'decimal');
$item = order($db, $p, 'A', 7, 300, 10000, 1, 1000);
check($p->shoppingAvailable(7) === 70000, 'spending reserved');
check((int)$db->fetchOne("SELECT COUNT(*) FROM program_ledger WHERE kind='buyer'") === 0, 'no prepayment reward');
paid($db, $p, 'A');
paid($db, $p, 'A');
$snapshot = json_decode($db->fetchOne("SELECT snapshot FROM program_orders WHERE id='A'"), true);
check($snapshot['items'][0]['paidMinor'] === 870000, 'basis excludes discounts spent delivery');
check(array_column($snapshot['recipients'], 'userId') === [6, 5, 4, 3, 1, 7], 'four levels and partner beyond depth 4');
check($p->dashboard(1)['balances']['commission']['pendingMinor'] === 8700, 'partner pending');
$p->changeTree(4, 3, true, 1, 'Fixture promotion');
order($db, $p, 'B');
$next = json_decode($db->fetchOne("SELECT snapshot FROM program_orders WHERE id='B'"), true);
check(array_column($next['recipients'], 'userId') === [6, 5, 4, 4, 7], 'detachment and dual partner reward');
check($db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=5') === 4, 'children retained');
check(array_column($snapshot['recipients'], 'userId') === [6, 5, 4, 3, 1, 7], 'old snapshot stable');
fails(static fn () => $p->changeTree(5, 7, false, 1, 'cycle'), 'cycle rejected');
fails(static fn () => $p->changeTree(5, 99, false, 1, 'missing'), 'missing parent');
$p->cancelOrder('B');
$p->cancelOrder('B');
$p->deliverOrder('A');
check($p->dashboard(1)['balances']['commission']['availableMinor'] === 0, 'hold');
$now = $now->modify('+15 days');
check($p->dashboard(1)['balances']['commission']['availableMinor'] === 8700, 'hold releases');
$p->updateSettings(['minimumWithdrawalMinor' => 1], 1);
$w = $p->requestWithdrawal(1, '80.00', ['recipient' => 'fixture']);
check($p->dashboard(1)['balances']['commission']['availableMinor'] === 700, 'payout reserve');
$p->updateWithdrawal($w['id'], 'rejected', null, 'fixture', 1);
check($p->dashboard(1)['balances']['commission']['availableMinor'] === 8700, 'payout rejection');
$w = $p->requestWithdrawal(1, '80.00', ['recipient' => 'fixture']);
$p->updateWithdrawal($w['id'], 'approved', null, null, 1);
fails(static fn () => $p->updateWithdrawal($w['id'], 'paid', null, null, 1), 'reference required');
$p->updateWithdrawal($w['id'], 'paid', 'fixture-transfer-1', null, 1);
$p->updateWithdrawal($w['id'], 'paid', 'fixture-transfer-1', null, 1);
$refund = $p->reserveRefund('A', 'refund-a', [['itemId' => $item, 'quantity' => 1]], true);
check($refund['amountMinor'] === 905000, 'refund cash basis');
check($p->dashboard(1)['balances']['commission']['debtMinor'] === 8000, 'refund reserve debt');
$p->cancelRefund('refund-a');
check($p->dashboard(1)['balances']['commission']['debtMinor'] === 0, 'failed refund release');
$p->reserveRefund('A', 'refund-a2', [['itemId' => $item, 'quantity' => 1]], true);
$p->completeRefund('refund-a2');
$p->completeRefund('refund-a2');
check($p->dashboard(1)['balances']['commission']['debtMinor'] === 8000, 'withdrawn commission debt');
check($p->shoppingAvailable(7) === 100000, 'spent shopping restored');
$round = order($db, $p, 'ROUND', 7, 0, 1, 3, 1);
paid($db, $p, 'ROUND');
$total = 0;
for ($i = 1; $i <= 3; ++$i) {
    $r = $p->reserveRefund('ROUND', 'round-' . $i, [['itemId' => $round, 'quantity' => 1]]);
    $total += $r['amountMinor'];
    $p->completeRefund('round-' . $i);
}check($total === 200, 'cumulative partial refund exact');
fails(static fn () => $p->reserveRefund('ROUND', 'extra', [['itemId' => $round, 'quantity' => 1]]), 'overrefund');
$p->adjust(7, 'shopping', 10000, 'fixture adjustment', 1);
$r1 = order($db, $p, 'RESERVE', 7, 500, 1000);
fails(static fn () => order($db, $p, 'OVER', 7, 1000, 1000), 'concurrent spend excludes reserves');
$p->cancelOrder('RESERVE');
check((int)$db->fetchOne("SELECT COUNT(*) FROM program_ledger WHERE kind='legacy_opening' AND wallet='commission'") === 0, 'old bonuses never commission');
$before = $p->settings();
fails(static fn () => $p->updateSettings(['buyerBps' => 10000, 'holdDays' => 0], 1), 'invalid partial settings');
check($p->settings() === $before, 'settings atomic rollback');
$p->updateSettings(['products' => ['1' => 0]], 1);
$excluded = order($db, $p, 'EXCLUDED');
paid($db, $p, 'EXCLUDED');
check((int)$db->fetchOne("SELECT SUM(amount_minor) FROM program_ledger WHERE order_id='EXCLUDED' AND state='pending'") === 0, 'product exclusion');
$p->updateSettings(['products' => [], 'holdDays' => 0], 1);
$held = order($db, $p, 'HELD', 7, 0, 1000);
paid($db, $p, 'HELD');
$p->reserveRefund('HELD', 'held-refund', [['itemId' => $held, 'quantity' => 1]]);
$p->deliverOrder('HELD');
$p->dashboard(4);
$p->completeRefund('held-refund');
check((int)$db->fetchOne("SELECT SUM(amount_minor) FROM program_ledger WHERE order_id='HELD' AND wallet='commission' AND state='available'") === 0, 'refund pending spans release');
check(count($p->listing('team', 4)['items']) === 3, 'team recursively lists descendants');
check($p->listing('sales', 4)['items'] !== [], 'sales available');
order($db, $p, 'INFLIGHT');
$db->insert('payment_operations', ['id' => 'inflight', 'order_id' => 'INFLIGHT', 'kind' => 'payment', 'status' => 'creating']);
fails(static fn () => $p->cancelOrder('INFLIGHT'), 'inflight payment protects bonus reservation');
foreach (['review_required', 'retry_required'] as $uncertain) {
    $db->update('payment_operations', ['status' => $uncertain], ['id' => 'inflight']);
    fails(static fn () => $p->cancelOrder('INFLIGHT'), 'uncertain payment protects bonus reservation');
}
$db->update('payment_operations', ['status' => 'canceled'], ['id' => 'inflight']);
$p->cancelOrder('INFLIGHT');
$db->update('site_settings', ['value' => 'false'], ['key' => 'referral_enabled']);
order($db, $p, 'NOREF');
$noRef = json_decode($db->fetchOne("SELECT snapshot FROM program_orders WHERE id='NOREF'"), true);
check(count($noRef['recipients']) === 1, 'disabled referrals omit upstream rewards');
echo "program-core: all assertions passed\n";
