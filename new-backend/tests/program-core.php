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
    $db->insert('user_profiles', ['user_id' => $i, 'bonus_balance' => $i === 7 ? 1000 : 0, 'is_partner' => $i === 1 ? 1 : 0, 'referred_by_user_id' => $i === 1 ? null : $i - 1, 'referral_code' => 'bf-' . $i]);
}
$db->insert('users', ['id' => 101, 'first_name' => 'Clean', 'last_name' => 'Account']);
$db->insert('user_profiles', ['user_id' => 101, 'bonus_balance' => 0, 'is_partner' => 0, 'referral_code' => 'bf-101']);
$p->dashboard(101);
$p->dashboard(101);
check((int)$db->fetchOne('SELECT COUNT(*) FROM program_ledger WHERE user_id=101') === 0, 'opening a clean account keeps history empty');
$p->adjust(101, 'shopping', 10000, 'First real bonus', 1);
check($p->dashboard(101)['balances']['shopping']['availableMinor'] === 10000, 'first bonus is not imported again as a legacy balance');
$p->adjust(101, 'shopping', -10000, 'Restore fixture balance', 1);
check($p->dashboard(7)['balances']['shopping']['availableMinor'] === 100000, 'existing nonzero legacy balance is preserved');
check($p->dashboard(7)['balances']['shopping']['availableMinor'] === 100000, 'legacy balance imports only once');

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
$p->joinTeam(6, $p->teamInvite(1)['code'], true);
check(ProgramMath::allocate(5, [1 => 3, 2 => 3, 3 => 3]) === [1 => 2, 2 => 2, 3 => 1], 'allocation');
check(ProgramMath::rules(['buyerBps' => 500])['buyerBps'] === 500, 'buyer rate is not limited by a total cap');
$converted = ProgramMath::rules([], ['directBps' => 220, 'teamBps' => 70, 'capBps' => 1]);
check($converted['partnerDirectBps'] === 220 && $converted['partnerMemberBps'] === 220 && $converted['memberDirectBps'] === 220 && $converted['partnerTeamBps'] === 70, 'legacy shared rates split without changing saved values');
check(!array_key_exists('capBps', $converted), 'legacy cap is discarded');
check(ProgramMath::minor('0.25') === 25, 'decimal');
check(ProgramMath::rules([], ['levelsBps' => [120, 60, 10, 10]])['partnerDirectBps'] === 120, 'stored legacy direct rate is retained');
fails(static fn () => ProgramMath::rules(['levelsBps' => [100, 50, 25, 25]]), 'new settings cannot re-enable four levels');
fails(static fn () => ProgramMath::rules(['levelsBps' => [100]]), 'both level rates required');
$p->updateSettings(['partnerTeamBps' => 100], 1);
$simulation = ProgramMath::simulate(['amount' => '10000', 'discountAmount' => '1000', 'costAmount' => '5000'], ProgramMath::rules(['partnerTeamBps' => 100]));
check($simulation['basisMinor'] === 900000 && $simulation['totalMinor'] === 27000, 'simulation net reward base');
check($simulation['directMinor'] === 9000 && $simulation['referralBonusMinor'] === 0 && $simulation['partnerMinor'] === 9000 && $simulation['buyerMinor'] === 9000, 'simulator separates direct, team and buyer rewards');
check($simulation['totalIncentivesMinor'] === 127000, 'simulation includes shop discount and rewards');
check($simulation['remainingAfterCostsMinor'] === 373000, 'simulation subtracts supplied operating costs');
fails(static fn () => ProgramMath::simulate(['amount' => '100', 'discountAmount' => '101'], ProgramMath::rules([])), 'discount cannot exceed goods');
$item = order($db, $p, 'A', 7, 300, 10000, 1, 1000);
check($p->shoppingAvailable(7) === 70000, 'spending reserved');
check((int)$db->fetchOne("SELECT COUNT(*) FROM program_ledger WHERE kind='buyer'") === 0, 'no prepayment reward');
paid($db, $p, 'A');
paid($db, $p, 'A');
$snapshot = json_decode($db->fetchOne("SELECT snapshot FROM program_orders WHERE id='A'"), true);
check($snapshot['items'][0]['paidMinor'] === 870000, 'basis excludes discounts spent delivery');
check(array_column($snapshot['recipients'], 'userId') === [6, 1, 7], 'direct member, explicit partner and buyer only');
check($p->dashboard(1)['balances']['commission']['pendingMinor'] === 8700, 'partner pending');
check((int)$db->fetchOne("SELECT COUNT(*) FROM program_ledger WHERE order_id='A' AND user_id IN (2,3,4) AND wallet='commission'") === 0, 'no commissions beyond two referral levels');
check((int)$db->fetchOne("SELECT SUM(amount_minor) FROM program_ledger WHERE order_id='A' AND wallet='commission'") === 17400, '8700 RUB pays 87 plus 87 commissions once');
$p->setPartnerStatus(4, true, 1, 'Fixture promotion');
order($db, $p, 'B');
$next = json_decode($db->fetchOne("SELECT snapshot FROM program_orders WHERE id='B'"), true);
check(array_column($next['recipients'], 'userId') === [6, 1, 7], 'unrelated promotion does not change explicit team');
check($db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=5') === 4, 'children retained');
check(array_column($snapshot['recipients'], 'userId') === [6, 1, 7], 'old snapshot stable');
check(!method_exists($p, 'changeTree'), 'manual referral transfer is removed');
$p->setPartnerStatus(5, false, 1, 'Same status');
check((int)$db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=5') === 4, 'unchanged status keeps inviter');
$p->setPartnerStatus(4, false, 1, 'Fixture demotion');
check($db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=4') === null, 'demotion does not reattach branch');
check((int)$db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=5') === 4, 'demotion keeps children');
$p->setPartnerStatus(4, true, 1, 'Fixture restore');
fails(static fn () => $p->setPartnerStatus(99, true, 1, 'Missing user'), 'missing user rejected');
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
fails(static fn () => $p->updateWithdrawal($w['id'], 'approved', null, null, 1), 'approval stage removed');
fails(static fn () => $p->updateWithdrawal($w['id'], 'rejected', null, null, 1), 'rejection reason required');
check($p->listing('withdrawals', 1)['items'][0]['user_name'] === 'Fixture 1', 'withdrawal recipient name');
check($p->listing('withdrawals', 6)['items'] === [], 'withdrawals scoped to owner');
fails(static fn () => $p->updateWithdrawal($w['id'], 'paid', null, null, 1), 'reference required');
$p->updateWithdrawal($w['id'], 'paid', 'fixture-transfer-1', null, 1);
$p->updateWithdrawal($w['id'], 'paid', 'fixture-transfer-1', null, 1);
check($p->dashboard(1)['balances']['commission']['availableMinor'] === 700, 'direct payment is idempotent');
fails(static fn () => $p->updateWithdrawal($w['id'], 'rejected', null, 'late rejection', 1), 'paid withdrawal cannot be rejected');
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
check($p->adminSettings()['bonusSpendLimitPercent'] === 30, 'spend limit defaults to existing checkout default');
$p->updateSettings(['bonusSpendLimitPercent' => 45], 1);
check($p->adminSettings()['bonusSpendLimitPercent'] === 45, 'admin settings read saved spend limit');
check(new App\Components\Setting\SiteSettings($db)->int('order_bonus_spend_limit_percent') === 45, 'checkout reads same saved spend limit');
$p->updateSettings(['holdDays' => 14], 1);
check($p->adminSettings()['bonusSpendLimitPercent'] === 45, 'partial rules update preserves spend limit');
fails(static fn () => $p->updateSettings(['bonusSpendLimitPercent' => 20, 'buyerBps' => 10001], 1), 'invalid rates reject combined save');
check($p->adminSettings()['bonusSpendLimitPercent'] === 45, 'invalid rules do not partially save spend limit');
foreach ([-1, 101, 1.5, null, '30'] as $invalidLimit) {
    fails(static fn () => $p->updateSettings(['bonusSpendLimitPercent' => $invalidLimit, 'holdDays' => 0], 1), 'invalid spend limit rejected');
    check($p->settings()['holdDays'] === 14, 'invalid spend limit leaves rules unchanged');
}
foreach ([0, 100, 30] as $validLimit) {
    $p->updateSettings(['bonusSpendLimitPercent' => $validLimit], 1);
    check($p->adminSettings()['bonusSpendLimitPercent'] === $validLimit, 'spend limit accepts boundaries');
}
$before = $p->settings();
fails(static fn () => $p->updateSettings(['buyerBps' => 10001, 'holdDays' => 0], 1), 'invalid partial settings');
check($p->settings() === $before, 'settings atomic rollback');
fails(static fn () => $p->updateSettings(['products' => ['1' => 0]], 1), 'removed product factors cannot be configured');
$db->update('program_locks', ['payload' => json_encode($before + ['products' => ['1' => 0]])], ['id' => 'settings']);
check(!array_key_exists('products', $p->settings()), 'legacy product factors are absent from current settings');
order($db, $p, 'FULL-BASE', 7, 0, 10000);
paid($db, $p, 'FULL-BASE');
check((int)$db->fetchOne("SELECT SUM(amount_minor) FROM program_ledger WHERE order_id='FULL-BASE' AND wallet='commission'") === 20000, 'legacy exclusion cannot reduce new order commissions');
$p->updateSettings(['holdDays' => 0], 1);
$held = order($db, $p, 'HELD', 7, 0, 1000);
paid($db, $p, 'HELD');
$p->reserveRefund('HELD', 'held-refund', [['itemId' => $held, 'quantity' => 1]]);
$p->deliverOrder('HELD');
$p->dashboard(4);
$p->completeRefund('held-refund');
check((int)$db->fetchOne("SELECT SUM(amount_minor) FROM program_ledger WHERE order_id='HELD' AND wallet='commission' AND state='available'") === 0, 'refund pending spans release');
$p->joinTeam(5, $p->teamInvite(1)['code'], true);
check(count($p->listing('team', 1)['items']) === 2, 'team lists explicit members only');
$sorted = $p->listing('team', 1, 1, 1, 'depth', 'asc')['items'];
check((int)$sorted[0]['id'] === 5, 'team sort applies before pagination');
check($sorted[0]['name'] === 'Fixture 5', 'team includes readable names');
check((int)$p->listing('team', 1, 2, 1, 'depth', 'asc')['items'][0]['id'] === 6, 'team second sorted page');
check((int)$p->listing('team', 1, 1, 1, 'name', 'desc')['items'][0]['id'] === 6, 'team name descending');
fails(static fn () => $p->listing('team', 1, 1, 25, 'unsafe SQL', 'asc'), 'reject unknown sort column');
fails(static fn () => $p->listing('team', 1, 1, 25, 'name', 'unsafe SQL'), 'reject unknown sort direction');
check($p->listing('sales', 1)['items'] !== [], 'sales available');
$p->adjust(6, 'commission', 123, 'wallet filter fixture', 1);
foreach (['shopping', 'commission'] as $wallet) {
    $expected = $db->fetchAllAssociative('SELECT id FROM program_ledger WHERE user_id=? AND wallet=? ORDER BY id DESC LIMIT 2 OFFSET 2', [6, $wallet]);
    $filtered = $p->listing('ledger', 6, 2, 2, wallet: $wallet)['items'];
    check(array_column($filtered, 'id') === array_column($expected, 'id'), 'wallet filtering precedes pagination');
    foreach ($p->listing('ledger', 6, limit: 100, wallet: $wallet)['items'] as $entry) {
        check($entry['wallet'] === $wallet && $entry['user_id'] === 6, 'wallet filtering keeps account ownership');
    }
}
fails(static fn () => $p->listing('ledger', 7, wallet: 'unknown'), 'invalid wallet rejected');

order($db, $p, 'INFLIGHT');
$db->insert('payment_operations', ['id' => 'inflight', 'order_id' => 'INFLIGHT', 'kind' => 'payment', 'status' => 'creating']);
fails(static fn () => $p->cancelOrder('INFLIGHT'), 'inflight payment protects bonus reservation');
foreach (['review_required', 'retry_required'] as $uncertain) {
    $db->update('payment_operations', ['status' => $uncertain], ['id' => 'inflight']);
    fails(static fn () => $p->cancelOrder('INFLIGHT'), 'uncertain payment protects bonus reservation');
}
$db->update('payment_operations', ['status' => 'canceled'], ['id' => 'inflight']);
$p->cancelOrder('INFLIGHT');
// An existing unbound customer earns for the QR owner, but joins only on payment.
$db->insert('users', ['id' => 8, 'first_name' => 'Unbound', 'email' => 'unbound@example.test']);
$db->insert('user_profiles', ['user_id' => 8, 'bonus_balance' => 0, 'is_partner' => 0, 'referral_code' => 'bf-8']);
order($db, $p, 'UNBOUND', 8);
$unbound = json_decode($db->fetchOne("SELECT snapshot FROM program_orders WHERE id='UNBOUND'"), true);
check(array_column($unbound['recipients'], 'userId') === [1, 8], 'unbound QR buyer must reward the partner');
check($db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=8') === null, 'unpaid order does not bind customer');
paid($db, $p, 'UNBOUND');
check((int)$db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=8') === 1, 'paid QR order binds existing customer');
check($p->dashboard(1)['balances']['commission']['pendingMinor'] > 0, 'QR partner gets commissions');
// Self-links and links from descendants must not prevent a normal purchase or create cycles.
$db->insert('users', ['id' => 9, 'first_name' => 'Cycle', 'email' => 'cycle@example.test']);
$db->insert('user_profiles', ['user_id' => 9, 'bonus_balance' => 0, 'is_partner' => 0, 'referral_code' => 'bf-9']);
order($db, $p, 'SELF-LINK', 9);
$p->cancelOrder('SELF-LINK');
$db->insert('users', ['id' => 10, 'first_name' => 'Child']);
$db->insert('user_profiles', ['user_id' => 10, 'bonus_balance' => 0, 'is_partner' => 0, 'referral_code' => 'bf-10', 'referred_by_user_id' => 9]);
foreach (['bf-9', 'bf-10'] as $code) {
    $id = 'CYCLE-' . $code;
    $db->insert('orders', ['id' => $id, 'user_id' => 9, 'total' => 1000, 'discount_amount' => 0, 'bonus_used' => 0, 'delivery_cost' => 0, 'payment_status' => 'pending', 'referred_by' => $code]);
    $db->insert('order_items', ['order_id' => $id, 'product_id' => 1, 'product_name' => 'Test', 'price' => 1000, 'quantity' => 1]);
    $p->captureOrder($id);
    paid($db, $p, $id);
    check($db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=9') === null, 'self or descendant link cannot bind');
    check((int)$db->fetchOne("SELECT COUNT(*) FROM program_ledger WHERE order_id=? AND wallet='commission'", [$id]) === 0, 'no commission for cyclic invitation');
}
order($db, $p, 'BECAME-PARTNER', 9);
$p->setPartnerStatus(9, true, 1, 'Promotion while order pending');
paid($db, $p, 'BECAME-PARTNER');
check($db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=9') === null, 'payment must not reattach promoted partner');
check((int)$db->fetchOne("SELECT COUNT(*) FROM program_ledger WHERE order_id='BECAME-PARTNER' AND wallet='commission'") === 0, 'no former team reward for provisional promoted buyer');
// A guest must not receive a cash commission on their own purchase.
$db->update('users', ['email' => 'partner@example.test'], ['id' => 1]);
$db->insert('orders', ['id' => 'SELF', 'user_id' => null, 'total' => 1000, 'discount_amount' => 0, 'bonus_used' => 0, 'delivery_cost' => 0, 'payment_status' => 'pending', 'referred_by' => 'bf-1', 'shipping_address' => json_encode(['email' => 'PARTNER@example.test'])]);
$db->insert('order_items', ['order_id' => 'SELF', 'product_id' => 1, 'product_name' => 'Test', 'price' => 1000, 'quantity' => 1]);
$p->captureOrder('SELF');
paid($db, $p, 'SELF');
check((int)$db->fetchOne("SELECT COUNT(*) FROM program_ledger WHERE order_id='SELF' AND wallet='commission' AND user_id=1") === 0, 'guest self referral must not pay buyer');
$db->update('site_settings', ['value' => 'false'], ['key' => 'referral_enabled']);
order($db, $p, 'NOREF');
$noRef = json_decode($db->fetchOne("SELECT snapshot FROM program_orders WHERE id='NOREF'"), true);
check(count($noRef['recipients']) === 1, 'disabled referrals omit upstream rewards');
// Journal filtering is applied before pagination; the end date includes the whole day.
foreach ([[1, '2020-01-01 00:00:00', 'commission'], [5, '2020-01-01 23:59:59', 'commission'], [6, '2020-01-02 00:00:00', 'commission'], [7, '2020-01-01 12:00:00', 'shopping']] as [$uid, $at, $wallet]) {
    $db->insert('program_ledger', ['id' => 'journal-test-' . $uid, 'user_id' => $uid, 'wallet' => $wallet, 'amount_minor' => 100, 'kind' => 'adjustment', 'state' => 'available', 'details' => '{}', 'created_at' => $at]);
}
$journal = $p->listing('ledger', null, limit: 100, sort: 'user_name', direction: 'asc', wallet: 'commission', dateFrom: '2020-01-01', dateTo: '2020-01-01')['items'];
check(array_column($journal, 'user_id') === [1, 5], 'journal inclusive dates, commission filter and user sort');
check($journal[0]['user_name'] === 'Fixture 1' && $journal[0]['participant_type'] === 'Партнёр', 'journal shows partner name');
check($journal[1]['participant_type'] === 'Участник команды', 'journal shows referral type');
$secondPage = $p->listing('ledger', null, page: 2, limit: 1, sort: 'user_name', direction: 'desc', wallet: 'commission', dateFrom: '2020-01-01', dateTo: '2020-01-01')['items'];
check(array_column($secondPage, 'user_id') === [1], 'journal sort across pages');
check($p->listing('ledger', null, sort: 'created_at', direction: 'desc', wallet: 'commission', dateFrom: '2020-01-01', dateTo: '2020-01-02')['items'][0]['user_id'] === 6, 'journal latest first');
check(count($p->listing('ledger', 5, wallet: 'commission', dateFrom: '2020-01-01', dateTo: '2020-01-02')['items']) === 1, 'date filter preserves account scope');
fails(static fn () => $p->listing('ledger', null, sort: 'unsafe SQL'), 'journal rejects unknown sort');
fails(static fn () => $p->listing('ledger', null, direction: 'unsafe SQL'), 'journal rejects unknown direction');
fails(static fn () => $p->listing('ledger', null, dateFrom: '2020-02-30'), 'journal rejects invalid calendar dates');
fails(static fn () => $p->listing('ledger', null, dateFrom: '2020-01-02', dateTo: '2020-01-01'), 'journal rejects reversed range');
// History uses dates rather than random audit IDs and resolves the people involved.
$db->insert('program_audit', ['id' => 'zz-old', 'actor_id' => 1, 'kind' => 'adjustment', 'payload' => '{"userId":6,"amountMinor":100,"wallet":"commission","reason":"fixture"}', 'created_at' => '2010-01-01 00:00:00']);
$db->insert('program_audit', ['id' => 'aa-new', 'actor_id' => 1, 'kind' => 'withdrawal', 'payload' => json_encode(['id' => $w['id'], 'status' => 'paid']), 'created_at' => '2090-01-01 00:00:00']);
$history = $p->listing('audit', null, limit: 100)['items'];
check($history[0]['id'] === 'aa-new', 'history newest first regardless of ID');
check($history[0]['actor_name'] === 'Fixture 1', 'history actor name');
check(((array)$history[0]['related_names'])[1] === 'Fixture 1' && $history[0]['withdrawal_amount_minor'] === 8000, 'history payout recipient and amount');
$oldHistory = array_values(array_filter($history, static fn ($row) => $row['id'] === 'zz-old'))[0];
check(((array)$oldHistory['related_names'])[6] === 'Fixture 6', 'history adjustment recipient name');
fails(static fn () => $p->listing('audit', 1), 'audit is admin only');
echo "program-core: all assertions passed\n";
