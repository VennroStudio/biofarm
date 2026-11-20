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
foreach (['cart_enabled', 'referral_enabled', 'order_bonus_enabled'] as $flag) {
    $db->insert('site_settings', ['key' => $flag, 'value' => 'true']);
}
$db->executeStatement('CREATE TABLE promo_code_redemptions (order_id TEXT, promo_code_id INTEGER)');
$db->executeStatement('CREATE TABLE payment_operations (id TEXT PRIMARY KEY, order_id TEXT, kind TEXT, status TEXT)');
$db->executeStatement('CREATE TABLE users (id INTEGER PRIMARY KEY,first_name TEXT,last_name TEXT,email TEXT,status INTEGER DEFAULT 1,deleted_at TEXT,created_at TEXT)');
$db->executeStatement('CREATE TABLE user_profiles (user_id INTEGER PRIMARY KEY,bonus_balance INTEGER,is_partner INTEGER,referred_by_user_id INTEGER,referral_code TEXT)');
$db->executeStatement('CREATE TABLE orders (id TEXT PRIMARY KEY,user_id INTEGER,total INTEGER,discount_amount INTEGER,bonus_used INTEGER,delivery_cost INTEGER,payment_status TEXT,referred_by TEXT,promo_code TEXT,status TEXT,updated_at TEXT,created_at TEXT,shipping_address TEXT)');
$db->executeStatement('CREATE TABLE order_items (id INTEGER PRIMARY KEY AUTOINCREMENT,order_id TEXT,product_id INTEGER,product_name TEXT,price INTEGER,quantity INTEGER)');
$now = new DateTimeImmutable('2026-09-17 12:00:00', new DateTimeZone('UTC'));
$p = new ProgramService($db, static function () use (&$now) {return $now; });
for ($i = 1; $i <= 7; ++$i) {
    $db->insert('users', ['id' => $i, 'first_name' => 'Fixture', 'last_name' => (string)$i]);
    $db->insert('user_profiles', ['user_id' => $i, 'bonus_balance' => $i === 7 ? 1000 : 0, 'is_partner' => $i === 1 ? 1 : 0, 'referred_by_user_id' => null, 'referral_code' => 'bf-' . $i]);
}
function order($db, $p, string $id, ?int $buyer = 7, int $bonus = 0, int $price = 10000, int $quantity = 1, int $discount = 0, string $ref = 'bf-1'): int
{
    $db->insert('orders', ['id' => $id, 'user_id' => $buyer, 'total' => $price * $quantity - $discount - $bonus + 350, 'discount_amount' => $discount, 'bonus_used' => $bonus, 'delivery_cost' => 350, 'payment_status' => 'pending', 'referred_by' => $ref]);
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

function rewards($db, string $id): array
{
    return array_map(static fn (array $r): array => [(int)$r['user_id'], $r['wallet'], $r['kind'], (int)$r['amount_minor']], $db->fetchAllAssociative('SELECT user_id,wallet,kind,amount_minor FROM program_ledger WHERE order_id=? ORDER BY user_id,kind', [$id]));
}
$p->updateSettings(['buyerBps' => 300, 'referralBonusBps' => 100, 'holdDays' => 14], 1);
$db->update('user_profiles', ['referred_by_user_id' => 1], ['user_id' => 2]);
$db->update('user_profiles', ['referred_by_user_id' => 2], ['user_id' => 3]);
foreach ([
    ['FIRST', 2, [[1, 'shopping', 'referral_bonus', 10000], [2, 'shopping', 'buyer', 30000]]],
    ['SECOND', 3, [[2, 'shopping', 'referral_bonus', 10000], [3, 'shopping', 'buyer', 30000]]],
    ['GUEST', null, [[1, 'shopping', 'referral_bonus', 10000]]],
] as [$id, $buyer, $expected]) {
    order($db, $p, $id, $buyer);
    check(rewards($db, $id) === [], 'no rewards until confirmed payment');
    paid($db, $p, $id);
    $p->settleOrder($id);
    check(rewards($db, $id) === $expected, 'buyer and direct referrer rewarded once: ' . $id);
}
check($p->dashboard(2)['balances']['shopping']['pendingMinor'] === 40000, 'own and invited purchases share a wallet');
check($p->dashboard(2)['bonusExpiry']['nextExpiryAt'] === null, 'undelivered bonuses have no expiry');
$now = new DateTimeImmutable('2027-01-17 12:00:00', new DateTimeZone('UTC'));
$p->deliverOrder('FIRST');
$p->deliverOrder('SECOND');
check($p->dashboard(2)['balances']['shopping']['availableMinor'] === 0, 'delivery does not skip hold');
$now = new DateTimeImmutable('2027-01-31 12:00:00', new DateTimeZone('UTC'));
$balance = $p->dashboard(2);
check($balance['balances']['shopping']['availableMinor'] === 40000, 'bonuses released after hold');
check($balance['bonusExpiry']['nextExpiryAt'] === '2027-02-28 12:00:00', 'expires a calendar month after release');
$now = new DateTimeImmutable('2027-02-28 11:59:59', new DateTimeZone('UTC'));
check($p->shoppingAvailable(2) === 40000, 'available until expiry');
$now = $now->modify('+1 second');
check($p->shoppingAvailable(2) === 0 && $p->shoppingAvailable(2) === 0, 'expiry exact and repeatable');

$p->setPartnerStatus(2, true, 1, 'fixture');
check($p->identity(2)['isPartner'] && $p->identity(2)['isReferral'], 'promotion preserves the inviter');
order($db, $p, 'LEGACY', 2);
$snapshot = json_decode($db->fetchOne("SELECT snapshot FROM program_orders WHERE id='LEGACY'"), true);
$snapshot['modelVersion'] = 2;
$snapshot['recipients'][1]['wallet'] = 'commission';
$snapshot['recipients'][1]['kind'] = 'direct';
$snapshot['items'][0]['rewards'][1]['wallet'] = 'commission';
$snapshot['items'][0]['rewards'][1]['kind'] = 'direct';
$db->update('program_orders', ['snapshot' => json_encode($snapshot)], ['id' => 'LEGACY']);
$p->updateSettings(['referralBonusBps' => 500], 1);
paid($db, $p, 'LEGACY');
check(rewards($db, 'LEGACY') === [[1, 'shopping', 'referral_bonus', 10000], [2, 'shopping', 'buyer', 30000]], 'pending legacy orders use saved rates and issue only bonuses');
fails(static fn () => $p->adjust(2, 'commission', 500, 'fixture', 1), 'cash adjustments unavailable');
fails(static fn () => $p->listing('sales', 3), 'partner order history restricted');
check(array_map('intval', array_column($p->listing('referrals', 1)['items'], 'id')) === [2], 'direct referral stays visible after promotion');
check(ProgramMath::rules([], ['levelsBps' => [0, 0]])['referralBonusBps'] === 0, 'legacy disabled rewards remain disabled');
echo "program-referrals: all assertions passed\n";
