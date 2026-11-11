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
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
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
    foreach (['testing_enabled' => false, 'cart_enabled' => true, 'registration_enabled' => true, 'referral_enabled' => true, 'promo_codes_enabled' => true, 'order_bonus_enabled' => true, 'order_emails_enabled' => false, 'bitrix_crm_enabled' => false] as $k => $v) {
        $db->executeStatement('INSERT INTO site_settings (`key`,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$k, json_encode($v)]);
    }
    $provider = [];
    $providerRequests = 0;
    $handler = static function (RequestInterface $req) use (&$provider, &$providerRequests) {
        ++$providerRequests;
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
        } catch (HttpNotFoundException $e) {
            ok($expected === 404, $method . ' ' . $url . ' unexpectedly missing');
            return null;
        } catch (HttpMethodNotAllowedException $e) {
            ok($expected === 405, $method . ' ' . $url . ' unexpectedly disallowed');
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
    $account = $call('GET', '/v1/users/me', [], $tokens[1]);
    ok($account['id'] === $ids[1] && $account['is_partner'] === true, 'partner profile loads after login');
    $account = $call('GET', '/v1/users/me', [], $tokens[2]);
    ok($account['id'] === $ids[2] && $account['is_partner'] === false, 'customer profile loads after login');
    $call('PATCH', '/admin/api/program/settings', ['referralBonusBps' => 100, 'buyerBps' => 100, 'holdDays' => 0], $admin);
    $call('PATCH', '/admin/api/program/settings', ['products' => ['1' => 0]], $admin, 422);
    $call('PATCH', '/admin/api/program/settings', ['capBps' => 400], $admin, 422);
    $call('PATCH', '/admin/api/program/settings', ['partnerDirectBps' => 200], $admin, 422);
    ok(array_keys($call('GET', '/admin/api/program/settings', [], $admin)) === ['referralBonusBps', 'buyerBps', 'holdDays', 'bonusSpendLimitPercent'], 'only bonus settings are exposed');
    foreach ([1, 3] as $user) {
        ok($call('GET', '/v1/users/me/referral-info', [], $tokens[$user])['referral_percent'] == 1, 'same referral rate for every role');
    }
    foreach ([['purchase', 10000, 0], ['referral', 10000, 10000], ['guest_referral', 0, 10000]] as [$scenario, $own, $referral]) {
        $result = $call('POST', '/admin/api/program/simulate', ['scenario' => $scenario, 'amount' => '10000'], $admin);
        ok($result['buyerMinor'] === $own && $result['referralBonusMinor'] === $referral, 'HTTP simulator: ' . $scenario);
    }
    $program->adjust($ids[1], 'shopping', 123, 'bonus fixture', $ids[0]);
    $history = $call('GET', '/v1/program/ledger?wallet=shopping&limit=1', [], $tokens[1]);
    ok(count($history['items']) === 1 && $history['items'][0]['wallet'] === 'shopping', 'single bonus history');
    ok((int)$history['items'][0]['user_id'] === $ids[1], 'bonus history is scoped');
    $call('GET', '/v1/program/ledger?wallet=unknown', [], $tokens[1], 422);
    $call('GET', '/v1/program/ledger?wallet=commission', [], $tokens[1], 422);
    $program->adjust($ids[1], 'shopping', -123, 'restore fixture balance', $ids[0]);

    $buyer = $tokens[6];
    $refs = $call('GET', '/v1/program/referrals', [], $tokens[1]);
    ok(array_map('intval', array_column($refs['items'], 'id')) === [$ids[2]], 'only direct referrals are listed');
    $call('GET', '/v1/program/team', [], $tokens[1], 404);
    $call('GET', '/v1/program/team-invitation', [], $tokens[1], 404);
    $call('POST', '/v1/program/join', [], $tokens[2], 404);
    $call('GET', '/v1/program/sales', [], $tokens[6], 422);

    ok($call('GET', '/v1/referrals/test-' . $suffix . '-1')['valid'] === true, 'existing referral is public and valid');
    ok($call('GET', '/v1/referrals/missing-' . $suffix)['valid'] === false, 'unknown referral rejected');
    $db->update('users', ['deleted_at' => gmdate('Y-m-d H:i:s')], ['id' => $ids[1]]);
    ok($call('GET', '/v1/referrals/test-' . $suffix . '-1')['valid'] === false, 'deleted referrer rejected');
    $db->update('users', ['deleted_at' => null], ['id' => $ids[1]]);
    $call('GET', '/v1/program', [], null, 401);
    $call('GET', '/admin/api/program', [], $buyer, 403);
    $dash = $call('GET', '/v1/program', [], $buyer);
    ok($dash['identity']['isReferral'] === true, 'derived referral flag');
    $product = (int)$db->fetchOne("SELECT id FROM products WHERE is_active=1 AND deleted_at IS NULL AND availability<>'out_of_stock' AND price>0 LIMIT 1");
    ok($product > 0, 'active product');
    $offer = $call('POST', '/v1/program/offers', ['title' => 'Тестовая корзина', 'items' => [['productId' => $product, 'quantity' => 2]]], $tokens[1]);
    ok($offer['url'] === '/checkout?offer=' . $offer['id'], 'new partner basket opens checkout directly');
    $offers = $call('GET', '/v1/program/offers', [], $tokens[1]);
    ok($offers['items'][0]['url'] === '/checkout?offer=' . $offer['id'], 'saved partner basket links open checkout directly');
    $public = $call('GET', '/v1/offers/' . $offer['id']);
    ok(count($public['items']) === 1, 'public shared basket');
    $privacy = ['personalDataConsent' => true, 'consentVersion' => '2026-10-04', 'marketingEmailConsent' => false];
    $body = $privacy + ['offerAccepted' => true, 'items' => [['productId' => $product, 'quantity' => 2]], 'paymentMethod' => 'card', 'deliveryMethod' => 'post', 'shippingAddress' => ['name' => 'Тестовый покупатель', 'email' => 'buyer@example.test', 'phone' => '+79990000000', 'city' => 'Томск', 'address' => 'Тестовая 1', 'postalCode' => '634000'], 'offerId' => $offer['id']];
    $requestHeaders = ['Idempotency-Key' => bin2hex(random_bytes(24))];
    $ordersBefore = (int)$db->fetchOne('SELECT COUNT(*) FROM orders');
    foreach (['personalDataConsent' => false, 'consentVersion' => 'old', 'offerAccepted' => false] as $field => $invalid) {
        $call('POST', '/v1/orders/create', array_replace($body, [$field => $invalid]), $buyer, 422);
    }
    ok((int)$db->fetchOne('SELECT COUNT(*) FROM orders') === $ordersBefore, 'invalid consent creates no order');
    $order = $call('POST', '/v1/orders/create', $body, $buyer, 201, $requestHeaders);
    $orderId = $order['id'];
    ok((int)$db->fetchOne("SELECT COUNT(*) FROM privacy_consents WHERE subject_type='order' AND subject_id=?", [$orderId]) === 3, 'order records privacy, optional marketing and offer separately');
    ok((int)$db->fetchOne("SELECT accepted FROM privacy_consents WHERE subject_id=? AND kind='marketing_email'", [$orderId]) === 0, 'declining marketing does not prevent checkout');
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
    $earn = $call('GET', '/v1/program', [], $tokens[5]);
    ok(array_keys($earn['balances']) === ['shopping'] && $earn['balances']['shopping']['availableMinor'] > 0, 'direct referrer earns shopping bonus');
    ok($call('GET', '/v1/program', [], $buyer)['balances']['shopping']['availableMinor'] > 0, 'buyer earns shopping bonus');
    ok($call('GET', '/v1/program', [], $tokens[4])['balances']['shopping']['availableMinor'] === 0, 'second level earns nothing');
    ok($call('GET', '/v1/program', [], $tokens[1])['balances']['shopping']['availableMinor'] === 0, 'basket owner cannot replace established direct referrer');
    $snapshot = $db->fetchOne('SELECT snapshot FROM program_orders WHERE id=?', [$orderId]);
    $call('PATCH', '/admin/api/program/users/' . $ids[3], ['parentId' => $ids[1]], $admin, 405);
    foreach (['parentId', 'referredByUserId', 'referred_by_user_id'] as $key) {
        $call('PATCH', '/admin/api/users/' . $ids[3], [$key => $ids[1]], $admin, 422);
    }
    ok((int)$db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$ids[3]]) === $ids[2], 'manual transfer is rejected');
    $call('PATCH', '/admin/api/users/' . $ids[3], ['isPartner' => true], $tokens[1], 403);
    $call('PATCH', '/admin/api/users/' . $ids[3], ['isPartner' => 'false'], $admin, 422);
    $call('PATCH', '/admin/api/users/' . $ids[3], ['isPartner' => true], $admin);
    ok((int)$db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$ids[3]]) === $ids[2], 'promoted partner keeps referrer');
    ok((int)$db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$ids[4]]) === $ids[3], 'descendants retained');
    ok($snapshot === $db->fetchOne('SELECT snapshot FROM program_orders WHERE id=?', [$orderId]), 'historical rewards immutable');
    $call('POST', '/v1/program/withdrawals', [], $tokens[1], 404);
    $call('PATCH', '/admin/api/program/withdrawals/retired', [], $admin, 405);
    $item = (int)$db->fetchOne('SELECT id FROM order_items WHERE order_id=?', [$orderId]);
    $refundBody = ['requestId' => 'fixture-refund-' . $suffix, 'items' => [['itemId' => $item, 'quantity' => 2]], 'refundDelivery' => true];
    $call('POST', '/admin/api/payments/orders/' . $orderId . '/refund', $refundBody, $admin);
    $call('POST', '/admin/api/payments/orders/' . $orderId . '/refund', $refundBody, $admin);
    ok($db->fetchOne('SELECT payment_status FROM orders WHERE id=?', [$orderId]) === 'refunded', 'full refund visible in customer order');
    $after = $call('GET', '/v1/program', [], $tokens[5]);
    ok($after['balances']['shopping']['availableMinor'] === 0, 'refund reverses the direct referral bonus');
    ok($call('GET', '/v1/program', [], $buyer)['balances']['shopping']['availableMinor'] === 0, 'refund reverses the buyer bonus');
    $db->update('partner_offers', ['is_active' => 0], ['id' => $offer['id']]);
    $replayed = $call('POST', '/v1/orders/create', $body, $buyer, 201, $requestHeaders);
    ok($replayed['id'] === $orderId, 'idempotency survives disabled offer');
    $db->update('partner_offers', ['is_active' => 1], ['id' => $offer['id']]);

    $call('GET', '/admin/api/program/audit', [], $admin);
    // A new guest follows the partner basket, pays without registering, and rewards its owner.
    $guestRules = $call('GET', '/admin/api/program/settings', [], $admin);
    $call('PATCH', '/admin/api/program/settings', ['referralBonusBps' => 300, 'buyerBps' => 100, 'holdDays' => 14], $admin);
    $originalPrice = $db->fetchOne('SELECT price FROM products WHERE id=?', [$product]);
    $db->update('products', ['price' => 1000], ['id' => $product]);
    $guestBody = $body;
    $guestBody['shippingAddress']['email'] = 'guest-' . $suffix . '@example.test';
    $usersBeforeGuest = (int)$db->fetchOne('SELECT COUNT(*) FROM users');
    $balanceBeforeGuest = $call('GET', '/v1/program', [], $tokens[1]);
    $call('GET', '/v1/offers/' . $offer['id']);
    $guest = $call('POST', '/v1/orders/create', $guestBody, null, 201);
    $guestId = $guest['id'];
    ok($db->fetchOne('SELECT user_id FROM orders WHERE id=?', [$guestId]) === null, 'guest order has no registered user');
    ok((string)$db->fetchOne('SELECT offer_id FROM partner_offer_orders WHERE order_id=?', [$guestId]) === $offer['id'], 'guest order attributed to shared basket');
    ok((int)$db->fetchOne('SELECT COUNT(*) FROM program_ledger WHERE order_id=?', [$guestId]) === 0, 'guest has no rewards before payment');
    $call('GET', '/v1/payments/' . $guestId, [], null, 403);
    $call('POST', '/v1/payments/' . $guestId, ['token' => $guest['paymentAccessToken']], null);
    ok((int)$db->fetchOne('SELECT COUNT(*) FROM program_ledger WHERE order_id=?', [$guestId]) === 0, 'pending guest payment earns nothing');
    $guestProviderId = $db->fetchOne("SELECT provider_id FROM payment_operations WHERE order_id=? AND kind='payment'", [$guestId]);
    $provider[$guestProviderId]['status'] = 'succeeded';
    $provider[$guestProviderId]['paid'] = true;
    $guestWebhook = ['event' => 'payment.succeeded', 'object' => ['id' => $guestProviderId]];
    $call('POST', '/webhooks/yookassa', $guestWebhook);
    $guestRewards = $db->fetchAllAssociative('SELECT user_id,wallet,kind,amount_minor,state FROM program_ledger WHERE order_id=? ORDER BY kind', [$guestId]);
    ok(count($guestRewards) === 1, 'guest checkout rewards only the referrer');
    ok(array_unique(array_map('intval', array_column($guestRewards, 'user_id'))) === [$ids[1]], 'guest referral bonus goes to basket partner');
    ok(array_column($guestRewards, 'wallet') === ['shopping'], 'guest purchase credits the shared bonus wallet');
    ok(array_column($guestRewards, 'kind') === ['referral_bonus'], 'guest direct referral bonus');
    ok(array_map('intval', array_column($guestRewards, 'amount_minor')) === [6000], '2000 RUB goods earn 60 RUB at 3 percent, excluding delivery');
    ok(array_column($guestRewards, 'state') === ['pending'], 'guest reward held until delivery and hold period');
    $call('POST', '/webhooks/yookassa', $guestWebhook);
    ok((int)$db->fetchOne('SELECT SUM(amount_minor) FROM program_ledger WHERE order_id=?', [$guestId]) === 6000, 'duplicate guest payment notification never doubles 60 RUB reward');
    $balanceAfterGuest = $call('GET', '/v1/program', [], $tokens[1]);
    ok($balanceAfterGuest['balances']['shopping']['pendingMinor'] - $balanceBeforeGuest['balances']['shopping']['pendingMinor'] === 6000, 'partner dashboard shows 60 RUB pending from guest');
    $guestSales = $call('GET', '/v1/program/sales', [], $tokens[1]);
    $guestSale = array_values(array_filter($guestSales['items'], static fn ($row) => $row['id'] === $guestId));
    ok(count($guestSale) === 1 && $guestSale[0]['payment_status'] === 'completed', 'partner sales show paid guest order');
    ok((int)$db->fetchOne('SELECT COUNT(*) FROM users') === $usersBeforeGuest, 'guest checkout and payment do not register a user');
    $call('POST', '/admin/api/program/orders/' . $guestId . '/delivered', [], $admin);
    $afterGuestDelivery = $call('GET', '/v1/program', [], $tokens[1]);
    ok($afterGuestDelivery['balances']['shopping']['pendingMinor'] === $balanceAfterGuest['balances']['shopping']['pendingMinor'], 'delivery does not skip guest reward hold period');
    $call('PATCH', '/admin/api/program/settings', $guestRules, $admin);
    $db->update('products', ['price' => $originalPrice], ['id' => $product]);
    echo "PASS guest basket: 2000 RUB goods, 60 partner bonuses (3%), held for delivery + 14 days; duplicate webhook safe; no user created\n";
    $registrationEmail = 'registered-' . $suffix . '@example.test';
    $call('POST', '/v1/users/create', ['firstName' => 'Тестовый', 'lastName' => 'Реферал', 'email' => $registrationEmail, 'password' => $password], null, 422);
    ok($db->fetchOne('SELECT id FROM users WHERE email=?', [$registrationEmail]) === false, 'registration requires explicit privacy consent');
    $call('POST', '/v1/users/create', $privacy + ['firstName' => 'Тестовый', 'lastName' => 'Реферал', 'email' => $registrationEmail, 'password' => $password, 'referredBy' => 'test-' . $suffix . '-5'], null, 201);
    $call('POST', '/v1/users/create', $privacy + ['firstName' => 'Тестовый', 'lastName' => 'Реферал', 'email' => $registrationEmail, 'password' => $password], null, 409);
    $registered = (int)$db->fetchOne('SELECT id FROM users WHERE email=?', [$registrationEmail]);
    ok((int)$db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$registered]) === $ids[5], 'registration attaches referral');
    ok((int)$db->fetchOne('SELECT bonus_balance FROM user_profiles WHERE user_id=?', [$registered]) === 0, 'no welcome balance');
    $call('GET', '/v1/team-invitations/test-' . $suffix . '-1', [], null, 404);
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
    ok($sim['totalIncentivesMinor'] === 118000 && $sim['remainingAfterCostsMinor'] === 382000, 'simulator includes all supplied costs');
    ok(!array_key_exists('maxPromoPercent', $call('GET', '/admin/api/program/settings', [], $admin)), 'partner promo setting retired');

    // Existing unbound account follows the first valid link, even with another partner's QR.
    $db->update('user_profiles', ['referred_by_user_id' => null], ['user_id' => $ids[2]]);
    $firstTouch = $body + ['referredBy' => 'test-' . $suffix . '-0'];
    $unbound = $call('POST', '/v1/orders/create', $firstTouch, $tokens[2], 201);
    $qrOnly = $call('POST', '/v1/orders/create', $body, $tokens[2], 201);
    $getSnapshot = static fn ($id) => json_decode($db->fetchOne('SELECT snapshot FROM program_orders WHERE id=?', [$id]), true);
    ok((int)$getSnapshot($unbound['id'])['referralParentId'] === $ids[0], 'first touch wins over QR owner');
    ok((int)$getSnapshot($qrOnly['id'])['referralParentId'] === $ids[1], 'QR works for registered unbound buyer');
    ok($db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$ids[2]]) === null, 'pending orders do not bind');
    $confirmPayment = static function (string $id) use ($call, $db, $tokens, &$provider): void {
        $call('POST', '/v1/payments/' . $id, [], $tokens[2]);
        $providerId = $db->fetchOne("SELECT provider_id FROM payment_operations WHERE order_id=? AND kind='payment'", [$id]);
        $provider[$providerId]['status'] = 'succeeded';
        $provider[$providerId]['paid'] = true;
        $call('POST', '/webhooks/yookassa', ['event' => 'payment.succeeded', 'object' => ['id' => $providerId]]);
    };
    $confirmPayment($unbound['id']);
    ok((int)$db->fetchOne('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$ids[2]]) === $ids[0], 'confirmed payment binds customer');
    $confirmPayment($qrOnly['id']);
    ok((int)$getSnapshot($qrOnly['id'])['referralParentId'] === $ids[0], 'later payment preserves established referrer');
    $bound = $call('POST', '/v1/orders/create', $body, $tokens[2], 201);
    ok((int)$getSnapshot($bound['id'])['referralParentId'] === $ids[0], 'QR never overwrites existing referrer');
    $guestFirst = $call('POST', '/v1/orders/create', $firstTouch, null, 201);
    ok((int)$getSnapshot($guestFirst['id'])['referralParentId'] === $ids[0], 'guest uses same first-touch priority');
    $selfBody = $body;
    $selfBody['shippingAddress']['email'] = 'program-' . $suffix . '-1@example.test';
    $selfOrder = $call('POST', '/v1/orders/create', $selfBody, null, 201);
    ok(!in_array($ids[1], array_column($getSnapshot($selfOrder['id'])['recipients'], 'userId'), true), 'guest self purchase excludes own referral bonus');

    // Testing is controlled only by the admin setting, never by the checkout payload.
    $call('PATCH', '/admin/api/settings', ['testing_enabled' => true], null, 401);
    $call('PATCH', '/admin/api/settings', ['testing_enabled' => true], $buyer, 403);
    $enabled = $call('PATCH', '/admin/api/settings', ['testing_enabled' => true], $admin);
    ok($enabled['testing_enabled'] === true, 'testing checkbox saves through admin API');
    $testingBody = $body;
    unset($testingBody['offerId']);
    $testingBody['referredBy'] = 'test-' . $suffix . '-1';
    $testingHeaders = ['Idempotency-Key' => bin2hex(random_bytes(24))];
    $requestsBeforeTesting = $providerRequests;
    $testingOrder = $call('POST', '/v1/orders/create', $testingBody, null, 201, $testingHeaders);
    ok($testingOrder['payment_status'] === 'completed', 'guest checkout immediately returns paid status');
    ok($db->fetchOne('SELECT payment_status FROM orders WHERE id=?', [$testingOrder['id']]) === 'completed', 'paid status persisted');
    $ledgerCount = (int)$db->fetchOne('SELECT COUNT(*) FROM program_ledger WHERE order_id=? AND amount_minor>0', [$testingOrder['id']]);
    ok($ledgerCount > 0, 'simulated payment creates normal rewards');
    $testingRetry = $call('POST', '/v1/orders/create', $testingBody, null, 201, $testingHeaders);
    ok($testingRetry['id'] === $testingOrder['id'] && $testingRetry['payment_status'] === 'completed', 'checkout retry returns same paid order');
    ok((int)$db->fetchOne('SELECT COUNT(*) FROM program_ledger WHERE order_id=? AND amount_minor>0', [$testingOrder['id']]) === $ledgerCount, 'checkout retry does not duplicate rewards');
    $access = ['X-Order-Token' => $testingOrder['paymentAccessToken']];
    $state = $call('GET', '/v1/payments/' . $testingOrder['id'], [], null, 200, $access);
    ok($state['orderPaymentStatus'] === 'completed' && $state['confirmationUrl'] === null, 'success page receives ordinary paid state without provider redirect');
    ok(!array_key_exists('testing', $state), 'no test label exposed to success page');
    $call('POST', '/v1/payments/' . $testingOrder['id'], [], null, 200, $access);
    ok($providerRequests === $requestsBeforeTesting, 'testing checkout and payment retries make no provider requests');
    $call('PATCH', '/admin/api/settings', ['testing_enabled' => false], $admin);
    $testingBody['testing_enabled'] = true;
    $ordinaryOrder = $call('POST', '/v1/orders/create', $testingBody, null, 201);
    ok($db->fetchOne('SELECT payment_status FROM orders WHERE id=?', [$ordinaryOrder['id']]) === 'pending', 'disabled testing restores normal checkout and ignores client override');

    echo "PASS program HTTP: {$checks} assertions, isolated test users, real routes and database; provider mocked; fixtures rolled back\n";
} finally {
    $db->rollBack();
}
