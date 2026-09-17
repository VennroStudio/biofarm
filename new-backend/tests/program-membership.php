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
$db->executeStatement('CREATE TABLE users (id INTEGER PRIMARY KEY,first_name TEXT,last_name TEXT,email TEXT,status INTEGER DEFAULT 1,deleted_at TEXT,created_at TEXT)');
$db->executeStatement('CREATE TABLE user_profiles (user_id INTEGER PRIMARY KEY,bonus_balance INTEGER,is_partner INTEGER,referred_by_user_id INTEGER,referral_code TEXT)');
$db->executeStatement('CREATE TABLE orders (id TEXT PRIMARY KEY,user_id INTEGER,total INTEGER,discount_amount INTEGER,bonus_used INTEGER,delivery_cost INTEGER,payment_status TEXT,referred_by TEXT,promo_code TEXT,status TEXT,updated_at TEXT,shipping_address TEXT)');
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

$invite = $p->teamInvite(1);
fails(static fn () => $p->teamInvite(2), 'ordinary customer cannot invite team members');
fails(static fn () => $p->joinTeam(2, $invite['code'], false), 'explicit consent required');
$p->joinTeam(2, $invite['code'], true);
$p->joinTeam(2, $invite['code'], true);
check($p->identity(2)['isTeamMember'] && $p->identity(2)['teamPartnerId'] === 1, 'separate membership');
check($db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=2') === null, 'membership is not purchase attribution');
$db->update('user_profiles', ['referred_by_user_id' => 2], ['user_id' => 3]);
$db->update('user_profiles', ['referred_by_user_id' => 3], ['user_id' => 4]);
$db->update('user_profiles', ['referred_by_user_id' => 1], ['user_id' => 5]);
check(!$p->identity(3)['isTeamMember'] && !$p->identity(5)['isTeamMember'], 'registered referrals never become team members automatically');
check(array_map('intval', array_column($p->listing('team', 1)['items'], 'id')) === [2], 'team contains only explicit member');
check(array_map('intval', array_column($p->listing('referrals', 2)['items'], 'id')) === [3], 'member sees only own registered referrals');
check(array_map('intval', array_column($p->listing('referrals', 1)['items'], 'id')) === [5], 'partner referrals exclude members and their buyers');
fails(static fn () => $p->listing('team', 2), 'member cannot read a team');
fails(static fn () => $p->listing('referrals', 3), 'ordinary buyer has no program tab');
fails(static fn () => $p->requestWithdrawal(3, '1', ['bank' => 'test']), 'ordinary buyer cannot withdraw');
function rewards($db, string $id): array
{
    return array_map(static fn (array $r): array => [(int)$r['user_id'], $r['wallet'], $r['kind'], (int)$r['amount_minor']], $db->fetchAllAssociative('SELECT user_id,wallet,kind,amount_minor FROM program_ledger WHERE order_id=? ORDER BY user_id,kind', [$id]));
}
order($db, $p, 'MEMBER', 2);
paid($db, $p, 'MEMBER');
check(rewards($db, 'MEMBER') === [[1, 'commission', 'team', 10000], [2, 'shopping', 'buyer', 10000]], 'member own purchase pays partner once');
$item = order($db, $p, 'MEMBER-CUSTOMER', 3);
paid($db, $p, 'MEMBER-CUSTOMER');
paid($db, $p, 'MEMBER-CUSTOMER');
check(rewards($db, 'MEMBER-CUSTOMER') === [[1, 'commission', 'team', 10000], [2, 'commission', 'direct', 10000], [3, 'shopping', 'buyer', 10000]], 'member customer pays member and partner once');
order($db, $p, 'NO-CASCADE', 4);
paid($db, $p, 'NO-CASCADE');
check(rewards($db, 'NO-CASCADE') === [[3, 'shopping', 'referral_bonus', 10000], [4, 'shopping', 'buyer', 10000]], 'ordinary referral pays bonuses only and stops chain');
order($db, $p, 'PARTNER-CUSTOMER', 5);
paid($db, $p, 'PARTNER-CUSTOMER');
check(rewards($db, 'PARTNER-CUSTOMER') === [[1, 'commission', 'direct', 10000], [5, 'shopping', 'buyer', 10000]], 'partner direct customer pays single commission');
order($db, $p, 'REPEAT', 3);
paid($db, $p, 'REPEAT');
check(rewards($db, 'REPEAT') === rewards($db, 'MEMBER-CUSTOMER'), 'registered repeat purchase keeps attribution');
order($db, $p, 'GUEST-MEMBER', null, ref: 'bf-2');
paid($db, $p, 'GUEST-MEMBER');
check(rewards($db, 'GUEST-MEMBER') === [[1, 'commission', 'team', 10000], [2, 'commission', 'direct', 10000]], 'guest gives member and partner cash without buyer cashback');
order($db, $p, 'GUEST-ORDINARY', null, ref: 'bf-3');
paid($db, $p, 'GUEST-ORDINARY');
check(rewards($db, 'GUEST-ORDINARY') === [[3, 'shopping', 'referral_bonus', 10000]], 'ordinary referrer gets only shopping bonuses for guest');
$db->update('site_settings', ['value' => 'false'], ['key' => 'order_bonus_enabled']);
order($db, $p, 'REFERRAL-NO-CASHBACK', 4);
paid($db, $p, 'REFERRAL-NO-CASHBACK');
check(rewards($db, 'REFERRAL-NO-CASHBACK') === [[3, 'shopping', 'referral_bonus', 10000]], 'own cashback flag does not disable referral bonuses');
$db->update('site_settings', ['value' => 'true'], ['key' => 'order_bonus_enabled']);
$p->deliverOrder('MEMBER-CUSTOMER');
$now = $now->modify('+15 days');
check($p->dashboard(2)['balances']['commission']['availableMinor'] === 10000, 'hold releases member money');
$p->reserveRefund('MEMBER-CUSTOMER', 'refund-member', [['itemId' => $item, 'quantity' => 1]], true);
$p->completeRefund('refund-member');
check((int)$db->fetchOne("SELECT SUM(amount_minor) FROM program_ledger WHERE order_id='MEMBER-CUSTOMER'") === 0, 'refund reverses all recipients');
$p->setPartnerStatus(2, true, 1, 'promotion');
check(!$p->identity(2)['isTeamMember'] && $p->identity(2)['isPartner'], 'promotion removes membership');
order($db, $p, 'PROMOTED', 3);
paid($db, $p, 'PROMOTED');
check(rewards($db, 'PROMOTED') === [[2, 'commission', 'direct', 10000], [3, 'shopping', 'buyer', 10000]], 'promoted partner keeps customers without old partner reward');
$sim = ProgramMath::simulate(['amount' => '10000', 'scenario' => 'team_customer'], ProgramMath::rules([]));
check($sim['totalMinor'] === 30000, 'maximum team plus buyer rewards are three percent');
fails(static fn () => ProgramMath::rules(['directBps' => 400]), 'maximum scenario respects cap');
// Leaving the program stops new cash rewards, never confiscates earned cash.
$p->adjust(2, 'commission', 50000, 'earned balance fixture', 1);
$p->setPartnerStatus(2, false, 1, 'demotion');
check(!$p->identity(2)['canEarnCommission'] && $p->identity(2)['hasCommissionHistory'], 'former participant retains history access');
check(count($p->listing('ledger', 2, wallet: 'commission')['items']) > 0, 'former participant can read own cash history');
$p->requestWithdrawal(2, '100', ['recipient' => 'fixture']);
fails(static fn () => $p->listing('referrals', 2), 'former participant cannot use active program');
$p->joinTeam(6, $invite['code'], true);
$p->adjust(6, 'commission', 50000, 'earned member balance', 1);
$p->setPartnerStatus(1, false, 1, 'partner demotion');
check(!$p->identity(6)['isTeamMember'], 'demotion ends membership');
$p->requestWithdrawal(6, '100', ['recipient' => 'fixture']);
check(ProgramMath::rules([], ['levelsBps' => [0, 0], 'partnerBps' => 0, 'buyerBps' => 0, 'capBps' => 0])['referralBonusBps'] === 0, 'legacy disabled rewards remain valid');
echo "program-membership: all assertions passed\n";
