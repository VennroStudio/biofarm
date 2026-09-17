<?php

declare(strict_types=1);
// Local integration: real routes/database + in-process fake payment provider. All fixtures roll back.
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Modules\Payment\Service\YooKassaGateway;
use App\Modules\Program\Service\ProgramService;
use App\Modules\User\Service\PasswordHasherService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

if (getenv('APP_ENV') !== 'dev') {
    throw new RuntimeException('Local development only');
}
$c = require dirname(__DIR__) . '/config/container.php';
$db = $c->get(Connection::class);
$db->beginTransaction();
$checks = 0;
function ok(bool $value, string $why): void
{
    global $checks;
    if (!$value) {
        throw new RuntimeException($why);
    } ++$checks;
}
try {
    foreach (['cart_enabled' => true, 'registration_enabled' => true, 'referral_enabled' => true, 'withdrawals_enabled' => true, 'promo_codes_enabled' => true, 'order_bonus_enabled' => true, 'order_emails_enabled' => false, 'bitrix_crm_enabled' => false] as $k => $v) {
        $db->executeStatement('INSERT INTO site_settings (`key`,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$k, json_encode($v)]);
    }
    $provider = [];
    $handler = static function (RequestInterface $req) use (&$provider) {
        $path = $req->getUri()->getPath();
        if ($req->getMethod() === 'POST') {
            $body = json_decode((string)$req->getBody(), true);
            $id = hash('sha256', $req->getHeaderLine('Idempotence-Key'));
            $provider[$id] ??= array_merge($body, ['id' => $id, 'status' => (str_ends_with($path, 'refunds') || str_ends_with($path, 'receipts')) ? 'succeeded' : 'pending', 'paid' => false, 'confirmation' => ['confirmation_url' => 'https://example.test/payment/' . $id]]);
            $data = $provider[$id];
        } else {
            $data = $provider[basename($path)] ?? null;
        }
        if ($data === null) {
            throw new RuntimeException('Unknown fake provider object');
        }
        return Create::promiseFor(new Response(200, ['Content-Type' => 'application/json'], json_encode($data)));
    };
    $c->set(YooKassaGateway::class, new YooKassaGateway(new Client(['handler' => $handler]), ['shopId' => 'fixture', 'secret' => 'fixture', 'returnBase' => 'https://example.test', 'receipts' => true, 'vatCode' => 1]));
    $c->set(MailerInterface::class, new class implements MailerInterface {
        public function send(RawMessage $message, ?Envelope $envelope = null): void {}
    });
    $app = (require dirname(__DIR__) . '/config/app.php')($c);
    $call = static function (string $method, string $url, array $body = [], ?string $token = null, int $expected = 200, array $headers = []) use ($app, $c) {
        $c->get(EntityManagerInterface::class)->clear();
        $r = new ServerRequestFactory()->createServerRequest($method, 'http://localhost:8088' . $url)->withParsedBody($body)->withHeader('Accept', 'application/json');
        foreach ($headers as $key => $value) {
            $r = $r->withHeader($key, $value);
        }
        if ($token !== null) {
            $r = $r->withHeader('Authorization', 'Bearer ' . $token);
        }
        try {
            $response = $app->handle($r);
        } catch (\Slim\Exception\HttpNotFoundException $e) {
            ok($expected === 404, $method . ' ' . $url . ' unexpectedly missing');
            return null;
        }
        $raw = (string)$response->getBody();
        $json = json_decode($raw, true);
        ok($response->getStatusCode() === $expected, $method . ' ' . $url . ' expected ' . $expected . ' got ' . $response->getStatusCode() . ' ' . substr($raw, 0, 500));
        return $json['data'] ?? $json;
    };
    $password = 'Fixture' . bin2hex(random_bytes(10)) . '!';
    $hash = $c->get(PasswordHasherService::class)->hash($password);
    $ids = [];
    $tokens = [];
    $suffix = bin2hex(random_bytes(5));
    for ($i = 0; $i < 7; ++$i) {
        $email = "program-{$suffix}-{$i}@example.test";
        $db->insert('users', ['role' => $i === 0 ? 1 : 4, 'last_name' => 'Проверка', 'first_name' => 'Участник ' . $i, 'email' => $email, 'password' => $hash, 'status' => 1, 'created_at' => gmdate('Y-m-d H:i:s')]);
        $id = (int)$db->lastInsertId();
        $ids[] = $id;
        $db->insert('user_profiles', ['user_id' => $id, 'bonus_balance' => 0, 'is_partner' => $i === 1 ? 1 : 0, 'referral_code' => 'test-' . $suffix . '-' . $i, 'referred_by_user_id' => $i > 1 ? $ids[$i - 1] : null, 'created_at' => gmdate('Y-m-d H:i:s')]);
        $login = $call('POST', '/v1/auth/login', ['email' => $email, 'password' => $password]);
        $tokens[] = $login['access_token'];
    }
    $admin = $tokens[0];
    $program = $c->get(ProgramService::class);
    $program->adjust($ids[1], 'shopping', 123, 'bonus separation fixture', $ids[0]);
    $program->adjust($ids[1], 'commission', 456, 'commission separation fixture', $ids[0]);
    foreach (['shopping', 'commission'] as $wallet) {
        $history = $call('GET', '/v1/program/ledger?wallet=' . $wallet . '&limit=1', [], $tokens[1]);
        ok(count($history['items']) === 1 && $history['items'][0]['wallet'] === $wallet, 'wallet filter through HTTP');
        ok((int)$history['items'][0]['user_id'] === $ids[1], 'wallet filter preserves owner');
    }
    $call('GET', '/v1/program/ledger?wallet=unknown', [], $tokens[1], 422);
    $program->adjust($ids[1], 'shopping', -123, 'restore fixture balance', $ids[0]);
    $program->adjust($ids[1], 'commission', -456, 'restore fixture balance', $ids[0]);

    $buyer = $tokens[6];
    $team = $call('GET', '/v1/program/team?sort=depth&direction=asc&limit=2&page=2', [], $tokens[1]);
    ok(array_map('intval', array_column($team['items'], 'id')) === [$ids[4], $ids[5]], 'team sorting precedes pagination through HTTP');
    ok($team['items'][0]['parent_name'] === 'Участник 3 Проверка', 'team returns human readable inviter');
    $teamDesc = $call('GET', '/v1/program/team?sort=name&direction=desc&limit=1', [], $tokens[1]);
    ok((int)$teamDesc['items'][0]['id'] === $ids[6], 'team name descending through HTTP');
    $call('GET', '/v1/program/team?sort=unknown', [], $tokens[1], 422);
    $call('GET', '/v1/program/team?direction=unknown', [], $tokens[1], 422);
    ok($call('GET', '/v1/program/team?userId=' . $ids[1], [], $tokens[6])['items'] === [], 'team query cannot select another account');

    ok($call('GET', '/v1/referrals/test-' . $suffix . '-1')['valid'] === true, 'existing referral is public and valid');
    ok($call('GET', '/v1/referrals/missing-' . $suffix)['valid'] === false, 'unknown referral rejected');
    $db->update('users', ['deleted_at' => gmdate('Y-m-d H:i:s')], ['id' => $ids[1]]);
    ok($call('GET', '/v1/referrals/test-' . $suffix . '-1')['valid'] === false, 'deleted referrer rejected');
    $db->update('users', ['deleted_at' => null], ['id' => $ids[1]]);
    $call('GET', '/v1/program', [], null, 401);
    $call('GET', '/admin/api/program', [], $buyer, 403);
    $dash = $call('GET', '/v1/program', [], $buyer);
    ok($dash['identity']['isReferral'] === true, 'derived referral flag');
    $call('PATCH', '/admin/api/program/settings', ['holdDays' => 0, 'minimumWithdrawalMinor' => 1], $admin);
    $product = (int)$db->fetchOne("SELECT id FROM products WHERE is_active=1 AND deleted_at IS NULL AND availability<>'out_of_stock' AND price>0 LIMIT 1");
    ok($product > 0, 'active product');
    $offer = $call('POST', '/v1/program/offers', ['title' => 'Тестовая корзина', 'items' => [['productId' => $product, 'quantity' => 2]]], $tokens[1]);
    $public = $call('GET', '/v1/offers/' . $offer['id']);
    ok(count($public['items']) === 1, 'public shared basket');
    $body = ['items' => [['productId' => $product, 'quantity' => 2]], 'paymentMethod' => 'card', 'deliveryMethod' => 'post', 'shippingAddress' => ['name' => 'Тестовый покупатель', 'email' => 'buyer@example.test', 'phone' => '+79990000000', 'city' => 'Томск', 'address' => 'Тестовая 1', 'postalCode' => '634000'], 'offerId' => $offer['id']];
    $requestHeaders = ['Idempotency-Key' => bin2hex(random_bytes(24))];
    $order = $call('POST', '/v1/orders/create', $body, $buyer, 201, $requestHeaders);
    $orderId = $order['id'];
    $sameOrder = $call('POST', '/v1/orders/create', $body, $buyer, 201, $requestHeaders);
    ok($sameOrder['id'] === $orderId && $sameOrder['paymentAccessToken'] === $order['paymentAccessToken'], 'lost create response reuses exact order and capability');
    $changed = $body;
    $changed['items'][0]['quantity'] = 3;
    $call('POST', '/v1/orders/create', $changed, $buyer, 409, $requestHeaders);

    ok((int)$db->fetchOne('SELECT COUNT(*) FROM program_ledger WHERE order_id=? AND amount_minor>0', [$orderId]) === 0, 'no rewards before payment');
    $call('POST', '/v1/payments/' . $orderId, [], $tokens[3], 403);
    $payment = $call('POST', '/v1/payments/' . $orderId, [], $buyer);
    ok($payment['status'] === 'pending', 'payment pending');
    $op = $db->fetchAssociative("SELECT * FROM payment_operations WHERE order_id=? AND kind='payment'", [$orderId]);
    $payload = json_decode($op['request_payload'], true);
    $receiptTotal = 0;
    foreach ($payload['receipt']['items'] as $line) {
        $receiptTotal += YooKassaGateway::minor($line['amount']['value']) * (int)$line['quantity'];
    }
    ok($receiptTotal === (int)$op['amount_minor'], 'receipt exact sum');
    try {
        $program->cancelOrder($orderId);
        throw new RuntimeException('active payment cancelled');
    } catch (DomainException) {
        ++$checks;
    }
    $provider[$op['provider_id']]['status'] = 'succeeded';
    $provider[$op['provider_id']]['paid'] = true;
    $webhook = ['event' => 'payment.succeeded', 'object' => ['id' => $op['provider_id'], 'amount' => ['value' => '0.01']]];
    $call('POST', '/webhooks/yookassa', $webhook);
    $count = (int)$db->fetchOne('SELECT COUNT(*) FROM program_ledger WHERE order_id=?', [$orderId]);
    $call('POST', '/webhooks/yookassa', $webhook);
    ok($count === (int)$db->fetchOne('SELECT COUNT(*) FROM program_ledger WHERE order_id=?', [$orderId]), 'duplicate webhook idempotent');
    $call('POST', '/admin/api/program/orders/' . $orderId . '/delivered', [], $admin);
    ok($db->fetchOne('SELECT status FROM orders WHERE id=?', [$orderId]) === 'delivered', 'delivery updates customer order status');
    ok((int)$db->fetchOne("SELECT COUNT(*) FROM payment_operations WHERE order_id=? AND kind='receipt' AND status='queued'", [$orderId]) === 1, 'delivery enqueues fiscal receipt');
    $call('POST', '/admin/api/payments/reconcile', [], $admin);
    ok($db->fetchOne("SELECT status FROM payment_operations WHERE order_id=? AND kind='receipt'", [$orderId]) === 'succeeded', 'MariaDB reconciliation processes delivery receipt');
    $settlement = $call('POST', '/admin/api/payments/orders/' . $orderId . '/receipt', [], $admin);
    ok($settlement['status'] === 'succeeded', 'delivery receipt provider verified');
    $again = $call('POST', '/admin/api/payments/orders/' . $orderId . '/receipt', [], $admin);
    ok($again['id'] === $settlement['id'], 'delivery receipt key stable');
    $earn = $call('GET', '/v1/program', [], $tokens[1]);
    ok($earn['balances']['commission']['availableMinor'] > 0, 'deep partner rewarded');
    $snapshot = $db->fetchOne('SELECT snapshot FROM program_orders WHERE id=?', [$orderId]);
    $call('PATCH', '/admin/api/program/users/' . $ids[3], ['isPartner' => true, 'parentId' => $ids[2], 'reason' => 'Проверка повышения'], $admin);
    ok($db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$ids[3]]) === null, 'promoted partner detached');
    ok((int)$db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$ids[4]]) === $ids[3], 'descendants retained');
    ok($snapshot === $db->fetchOne('SELECT snapshot FROM program_orders WHERE id=?', [$orderId]), 'historical rewards immutable');
    $w = $call('POST', '/v1/program/withdrawals', ['amount' => '0.01', 'details' => ['recipient' => 'Тестовые реквизиты']], $tokens[1]);
    $call('PATCH', '/admin/api/program/withdrawals/' . $w['id'], ['status' => 'approved'], $admin);
    $call('PATCH', '/admin/api/program/withdrawals/' . $w['id'], ['status' => 'paid', 'reference' => 'fixture-' . $suffix], $admin);
    $item = (int)$db->fetchOne('SELECT id FROM order_items WHERE order_id=?', [$orderId]);
    $refundBody = ['requestId' => 'fixture-refund-' . $suffix, 'items' => [['itemId' => $item, 'quantity' => 2]], 'refundDelivery' => true];
    $call('POST', '/admin/api/payments/orders/' . $orderId . '/refund', $refundBody, $admin);
    $call('POST', '/admin/api/payments/orders/' . $orderId . '/refund', $refundBody, $admin);
    ok($db->fetchOne('SELECT payment_status FROM orders WHERE id=?', [$orderId]) === 'refunded', 'full refund visible in customer order');
    $after = $call('GET', '/v1/program', [], $tokens[1]);
    ok($after['balances']['commission']['debtMinor'] === 1, 'refund after manual payout creates debt');
    $db->update('partner_offers', ['is_active' => 0], ['id' => $offer['id']]);
    $replayed = $call('POST', '/v1/orders/create', $body, $buyer, 201, $requestHeaders);
    ok($replayed['id'] === $orderId, 'idempotency survives disabled offer');
    $db->update('partner_offers', ['is_active' => 1], ['id' => $offer['id']]);

    $call('GET', '/admin/api/program/audit', [], $admin);
    // Guest basket checkout needs only its unguessable payment access token.
    $guest = $call('POST', '/v1/orders/create', $body, null, 201);
    $call('GET', '/v1/payments/' . $guest['id'], [], null, 403);
    $call('POST', '/v1/payments/' . $guest['id'], ['token' => $guest['paymentAccessToken']], null);
    $registrationEmail = 'registered-' . $suffix . '@example.test';
    $call('POST', '/v1/users/create', ['firstName' => 'Тестовый', 'lastName' => 'Реферал', 'email' => $registrationEmail, 'password' => $password, 'referredBy' => 'test-' . $suffix . '-5'], null, 201);
    $registered = (int)$db->fetchOne('SELECT id FROM users WHERE email=?', [$registrationEmail]);
    ok((int)$db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$registered]) === $ids[5], 'registration attaches referral');
    ok((int)$db->fetchOne('SELECT bonus_balance FROM user_profiles WHERE user_id=?', [$registered]) === 0, 'no welcome balance');
    // Removed partner promo endpoints must stay unavailable; ordinary shop codes still work.
    $call('POST', '/v1/program/promo-requests', ['description' => 'Removed', 'percent' => 3], $tokens[1], 410);
    $call('GET', '/admin/api/program/promo-requests', [], $admin, 410);
    $promoCode = 'TEST-' . strtoupper($suffix);
    $shopPromo = $call('POST', '/admin/api/promo-codes', ['code' => $promoCode, 'type' => 'percent', 'value' => 3, 'usage_limit' => 1], $admin, 201);
    $promoBody = $body;
    unset($promoBody['offerId']);
    $promoBody['promoCode'] = $promoCode;
    $promoOrder = $call('POST', '/v1/orders/create', $promoBody, $tokens[2], 201);
    ok((int)$db->fetchOne('SELECT discount_amount FROM orders WHERE id=?', [$promoOrder['id']]) > 0, 'ordinary shop promo discount still works');
    $exhausted = $call('POST', '/v1/orders/create', $promoBody, $tokens[2], 201);
    ok((int)$db->fetchOne('SELECT discount_amount FROM orders WHERE id=?', [$exhausted['id']]) === 0, 'exhausted ordinary code gives no discount');
    $program->cancelOrder($promoOrder['id']);
    $db->update('orders', ['status' => 'cancelled'], ['id' => $promoOrder['id']]);
    ok((int)$db->fetchOne('SELECT used_count FROM promo_codes WHERE code=?', [$promoCode]) === 0, 'cancel releases ordinary promo usage');
    $program->cancelOrder($promoOrder['id']);
    $call('POST', '/v1/orders/create', $promoBody, $tokens[2], 201);

    $retiredCode = 'OLD-PARTNER-' . strtoupper($suffix);
    $retired = $call('POST', '/admin/api/promo-codes', ['code' => $retiredCode, 'type' => 'percent', 'value' => 3], $admin, 201);
    $db->insert('partner_promo_requests', ['id' => bin2hex(random_bytes(16)), 'user_id' => $ids[1], 'status' => 'approved', 'request' => '{}', 'rules' => '{}', 'promo_code_id' => $retired['id'], 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')]);
    $retiredBody = $promoBody;
    $retiredBody['promoCode'] = $retiredCode;
    $call('POST', '/v1/orders/create', $retiredBody, $tokens[2], 422);
    $noPromoOffer = $call('POST', '/v1/program/offers', ['title' => 'Без автопромокода', 'promoCode' => $retiredCode, 'items' => [['productId' => $product, 'quantity' => 1]]], $tokens[1]);
    ok(!array_key_exists('promoCode', $call('GET', '/v1/offers/' . $noPromoOffer['id'])), 'QR no longer imports a promo');
    $sim = $call('POST', '/admin/api/program/simulate', ['amount' => '10000', 'discountAmount' => '1000', 'costAmount' => '5000'], $admin);
    ok($sim['totalIncentivesMinor'] === 136000 && $sim['remainingAfterCostsMinor'] === 364000, 'simulator includes all supplied costs');
    ok(!array_key_exists('maxPromoPercent', $call('GET', '/admin/api/program/settings', [], $admin)), 'partner promo setting retired');

    // Existing unbound account follows the first valid link, even with another partner's QR.
    $db->update('user_profiles', ['referred_by_user_id' => null], ['user_id' => $ids[2]]);
    $firstTouch = $body + ['referredBy' => 'test-' . $suffix . '-3'];
    $unbound = $call('POST', '/v1/orders/create', $firstTouch, $tokens[2], 201);
    $qrOnly = $call('POST', '/v1/orders/create', $body, $tokens[2], 201);
    $getSnapshot = static fn ($id) => json_decode($db->fetchOne('SELECT snapshot FROM program_orders WHERE id=?', [$id]), true);
    ok((int)$getSnapshot($unbound['id'])['recipients'][0]['userId'] === $ids[3], 'first touch wins over QR owner');
    ok((int)$getSnapshot($qrOnly['id'])['recipients'][0]['userId'] === $ids[1], 'QR works for registered unbound buyer');
    ok($db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$ids[2]]) === null, 'pending orders do not bind');
    $confirmPayment = static function (string $id) use ($call, $db, $tokens, &$provider): void {
        $call('POST', '/v1/payments/' . $id, [], $tokens[2]);
        $providerId = $db->fetchOne("SELECT provider_id FROM payment_operations WHERE order_id=? AND kind='payment'", [$id]);
        $provider[$providerId]['status'] = 'succeeded';
        $provider[$providerId]['paid'] = true;
        $call('POST', '/webhooks/yookassa', ['event' => 'payment.succeeded', 'object' => ['id' => $providerId]]);
    };
    $confirmPayment($unbound['id']);
    ok((int)$db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$ids[2]]) === $ids[3], 'confirmed payment binds customer');
    $confirmPayment($qrOnly['id']);
    ok((int)$getSnapshot($qrOnly['id'])['recipients'][0]['userId'] === $ids[3], 'later payment preserves established team');
    $bound = $call('POST', '/v1/orders/create', $body, $tokens[2], 201);
    ok((int)$getSnapshot($bound['id'])['recipients'][0]['userId'] === $ids[3], 'QR never overwrites existing team');
    $guestFirst = $call('POST', '/v1/orders/create', $firstTouch, null, 201);
    ok((int)$getSnapshot($guestFirst['id'])['recipients'][0]['userId'] === $ids[3], 'guest uses same first-touch priority');
    $selfBody = $body;
    $selfBody['shippingAddress']['email'] = 'program-' . $suffix . '-1@example.test';
    $selfOrder = $call('POST', '/v1/orders/create', $selfBody, null, 201);
    ok(!in_array($ids[1], array_column($getSnapshot($selfOrder['id'])['recipients'], 'userId'), true), 'guest self purchase excludes own commission');

    echo "PASS program HTTP: {$checks} assertions, 8 test users, real routes and database; provider mocked; fixtures rolled back\n";
} finally {
    $db->rollBack();
}
