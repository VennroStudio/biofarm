<?php

declare(strict_types=1);

use App\Components\Auth\AccessTokenPayload;
use App\Components\Auth\JwtTokenService;
use App\Components\Integration\Credential\IntegrationCredentialStore;
use App\Components\Privacy\ConsentRecorder;
use App\Modules\Ozon\OzonApi;
use App\Modules\Ozon\OzonConnection;
use App\Modules\Ozon\OzonFulfillment;
use App\Modules\Ozon\OzonPickupDirectory;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\Payment\Service\YooKassaGateway;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Service\Phone\SmsAero;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Log\NullLogger;
use Slim\Psr7\Factory\ServerRequestFactory;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/ozon-test-tables.php';

putenv('REDIS=false');
putenv('OZON_DELIVERY_ENABLED=1');
putenv('OZON_CLIENT_ID=fixture');
putenv('OZON_CLIENT_SECRET=fixture');
putenv('OZON_SELLER_ID=123');
putenv('OZON_REDIRECT_URI=http://localhost:8094/ozon/callback');
$container = require dirname(__DIR__) . '/config/container.php';
$db = $container->get(Connection::class);
$tables = [
    'site_settings', 'categories', 'products', 'users', 'user_profiles', 'user_addresses',
    'orders', 'order_items', 'checkout_requests', 'promo_codes', 'promo_code_redemptions',
    'ozon_catalog_products', 'ozon_catalog_state', 'ozon_delivery_orders',
    'integration_credentials', 'integration_error_logs', 'privacy_consents', 'payment_operations', 'payment_sessions',
    'program_locks', 'program_orders', 'program_refunds', 'program_ledger', 'program_audit',
];
createOzonTestTables($db, $tables);
createStockTestSchema($db);
$tablesOnServer = $db->createSchemaManager()->listTableNames();
createOzonTestTables($db, array_intersect(['ozon_pickup_points', 'ozon_delivered_postings', 'phone_verification_challenges'], $tablesOnServer));

// Match the release schema on this connection while leaving the test server unchanged.
$migrated = $db->fetchFirstColumn('SELECT version FROM migrations');
foreach ([20261116120000, 20261116130000, 20261116140000, 20261116150000, 20261116160000] as $version) {
    $class = 'App\\Migrations\\Version' . $version;
    if (in_array($class, $migrated, true)) {
        continue;
    }
    $migration = new $class($db, new NullLogger());
    $migration->up(new Schema());
    foreach ($migration->getSql() as $query) {
        $sql = $query->getStatement();
        if (preg_match('/^ALTER TABLE ([a-z_]+)/', $sql, $match) && in_array($match[1], $tables, true)) {
            $db->executeStatement($sql);
        } elseif (preg_match('/^CREATE TABLE (ozon_pickup_points|ozon_delivered_postings|phone_verification_challenges) \(/', $sql)) {
            $db->executeStatement(str_replace('CREATE TABLE ', 'CREATE TEMPORARY TABLE ', $sql));
        } else {
            throw new RuntimeException('Unexpected schema change in the Ozon delivery test.');
        }
    }
}

function check(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

$sku = 5082220452;
$point = 2643632;
$date = '2026-12-01T10:00:00Z';
$email = 'buyer@example.test';
$nativeMarks = ["010460123456789021EXAMPLE1\x1d93AAAA", "010460123456789021EXAMPLE2\x1d93BBBB", "010460123456789021EXAMPLE3\x1d93CCCC"];
$providerPayments = $providerRefunds = $providerReceipts = $created = $postingStatuses = $cancelled = [];
$refundCalls = $createCalls = 0;
$pickedStatus = 'awaiting_deliver';
$unknownQuote = $fbsTrackingOffline = $fbsRefusal = $fbsRefusalIdentified = false;
$partial = $pendingCancellation = $lostCreate = $splitCheckout = false;

$reply = static fn (array $body, int $http = 200) => Create::promiseFor(new Response($http, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR)));
$ozon = static function (RequestInterface $request) use ($reply, $point, $sku, $date, $email, $nativeMarks, &$created, &$pickedStatus, &$partial, &$pendingCancellation, &$lostCreate, &$createCalls, &$splitCheckout, &$postingStatuses, &$cancelled, &$unknownQuote, &$fbsTrackingOffline, &$fbsRefusal, &$fbsRefusalIdentified) {
    check($request->getUri()->getHost() === 'api-seller.ozon.ru', 'production path must use Seller API');
    check(str_starts_with($request->getHeaderLine('Authorization'), 'Bearer '), 'authenticated Seller API request');
    $in = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
    $path = $request->getUri()->getPath();
    $num = $in['filter']['order_numbers'][0] ?? $in['order_number'] ?? null;
    $address = ['enabled' => true, 'delivery_method' => [
        'map_point_id' => $point, 'address' => 'Россия, Томск, проспект Ленина, 1',
        'address_details' => ['city' => 'Томск'], 'delivery_type' => ['id' => 1002, 'name' => 'Самовывоз'],
    ]];
    switch ($path) {
        case '/v1/delivery/point/list':
            return $reply(['points' => [['map_point_id' => $point]]]);
        case '/v1/delivery/point/info':
            return $reply(['points' => [$address]]);
        case '/v1/delivery/check':
            check($in['client_phone'] === '79000000000', 'live checks the verified storefront phone');
            return $reply(['is_possible' => true]);
        case '/v2/delivery/checkout':
            check($in['delivery_type'] === ['pick_up' => ['map_point_id' => $point]], 'pickup only');
            if ($unknownQuote) {
                return $reply(['splits' => [['unavailable_reason' => 'UNKNOWN']]]);
            }
            if ($splitCheckout && $in['delivery_schema'] !== 'MIX') {
                return $reply(['splits' => []]);
            }
            $split = [
                'delivery_schema' => $in['delivery_schema'] === 'FBS' ? 'FBS' : 'FBO', 'warehouse_id' => 17, 'items' => $in['items'],
                'commissions' => ['total' => ['currency' => 'RUB', 'amount' => '30']],
                'delivery_method' => ['id' => 12, 'delivery_type' => 'PVZ', 'timeslots' => [
                    ['timeslot_id' => 13, 'logistic_date_range' => ['from' => $date, 'to' => $date]],
                ]],
            ];
            if (count($in['items']) === 2) {
                check($in['delivery_schema'] === 'MIX', 'different products require both schemes');
                $second = $split;
                $split['delivery_schema'] = 'FBO';
                $split['items'] = [$in['items'][0]];
                $second['delivery_schema'] = 'FBS';
                $second['warehouse_id'] = 18;
                $second['items'] = [['sku' => $sku + 101, 'quantity' => 1]];
                return $reply(['splits' => [$split, $second]]);
            }
            if (!$splitCheckout) {
                return $reply(['splits' => [$split]]);
            }
            check($in['items'] === [['sku' => $sku, 'quantity' => 3]], 'fixture checkout receives one cart with three items');
            $second = $split;
            $split['items'][0]['quantity'] = 1;
            $split['commissions']['total']['amount'] = '12';
            $second['items'][0]['quantity'] = 2;
            $second['warehouse_id'] = 18;
            $second['delivery_schema'] = 'FBS';
            $second['commissions']['total']['amount'] = '18';
            $second['delivery_method']['timeslots'][0]['logistic_date_range'] = ['from' => '2026-12-02T10:00:00Z', 'to' => '2026-12-02T10:00:00Z'];
            return $reply(['splits' => [$second, $split]]);
        case '/v2/order/create':
            ++$createCalls;
            if ($lostCreate) {
                return $reply(['message' => 'lost create response'], 503);
            }
            check($in['buyer']['email'] === $email && $in['recipient']['recipient_phone'] === '79000000000', 'real buyer contact details');
            check($in['delivery'] === ['pick_up' => ['map_point_id' => $point]], 'canonical pickup destination');
            $num = '3123456700-' . str_pad((string)$createCalls, 4, '0', STR_PAD_LEFT);
            $created[$num] = $in;
            return $reply(['order_number' => $num, 'postings' => [$num . '-1']]);
        case '/v4/posting/fbs/list':
        case '/v3/posting/fbo/list':
            if ($path === '/v4/posting/fbs/list' && $fbsTrackingOffline) {
                return $reply(['message' => 'unavailable'], 503);
            }
            check(isset($created[$num]), 'must track the correct Ozon order');
            $postings = [];
            foreach ($created[$num]['splits'] as $split) {
                $isFbs = $created[$num]['delivery_schema'] === 'FBS' || ($created[$num]['delivery_schema'] === 'MIX' && $split['warehouse_id'] === 18);
                if ($isFbs !== ($path === '/v4/posting/fbs/list')) continue;
                $posting = $num . '-' . ($split['warehouse_id'] === 17 ? '1' : '2');
                $status = isset($cancelled[$num]) ? 'cancelled' : ($postingStatuses[$posting] ?? $pickedStatus);
                if ($status === 'unlisted') {
                    continue;
                }
                $postings[] = [
                    'order_number' => $num, 'posting_number' => $posting, 'status' => $status,
                    'substatus' => match ($status) {
                        'delivered' => 'posting_received', 'cancelled' => 'posting_canceled', default => 'posting_in_pickup_point',
                    },
                    'products' => [['sku' => $sku, 'quantity' => $split['items'][0]['quantity']]],
                ];
            }
            if ($partial && count($postings) === 1 && $postings[0]['status'] === 'delivered') {
                $postings[] = [
                    'order_number' => $num, 'posting_number' => $num . '-2',
                    'status' => 'cancelled', 'substatus' => 'posting_canceled',
                    'products' => [['sku' => $sku, 'quantity' => 1]],
                ];
            }
            return $reply(['postings' => $postings]);
        case '/v3/posting/fbs/get':
            $posting = $in['posting_number'];
            $codes = str_ends_with($posting, '-2') ? array_slice($nativeMarks, 1) : array_slice($nativeMarks, 0, 2);
            return $reply(['result' => ['posting_number' => $posting, 'product_exemplars' => ['products' => [[
                'sku' => $sku, 'exemplars' => array_map(static fn ($i, $mark) => ['exemplar_id' => $i + 1, 'mandatory_mark' => $mark], array_keys($codes), $codes),
            ]]]]]);
        case '/v1/posting/marks':
            $posting = $in['posting_numbers'][0];
            $first = ['exemplar_id' => 1, 'sku' => $sku, 'posting_number' => $posting, 'mandatory_marks' => [$nativeMarks[0]]];
            $second = ['exemplar_id' => 2, 'sku' => $sku, 'posting_number' => $posting, 'mandatory_marks' => [$nativeMarks[1]]];
            $num = substr($posting, 0, -2);
            if (count($created[$num]['splits']) > 1) {
                $third = ['exemplar_id' => 3, 'sku' => $sku, 'posting_number' => $posting, 'mandatory_marks' => [$nativeMarks[2]]];
                return $reply(['issued_exemplars' => str_ends_with($posting, '-1') ? [$first] : [$second, $third]]);
            }
            return $reply(['issued_exemplars' => $partial ? [$first] : [$first, $second],
                'non_issued_exemplars' => $partial ? [array_diff_key($second, ['mandatory_marks' => true])] : []]);
        case '/v1/returns/list':
            $posting = $in['filter']['posting_numbers'][0];
            return $reply(['returns' => !$fbsRefusal ? [] : [[
                'id' => 22, 'type' => 'PartialReturn', 'order_number' => substr($posting, 0, -2),
                'posting_number' => $posting, 'product' => ['sku' => $sku, 'quantity' => 1],
                'exemplars' => $fbsRefusalIdentified ? [['id' => 1]] : [],
            ]], 'has_next' => false]);
        case '/v1/order/cancel/check':
            return $reply(['cancellable' => true]);
        case '/v1/cancel-reason/list-by-order':
            return $reply(['reasons' => [['id' => 508]]]);
        case '/v1/order/cancel':
            check(isset($created[$num]) && $in['reason_id'] === 508, 'only Ozon cancellation reasons');
            $pendingCancellation = true;
            return $reply([]);
        case '/v1/order/cancel/status':
            if (!$pendingCancellation) {
                $cancelled[$num] = true;
            }
            return $reply(['order_number' => $num, 'state' => $pendingCancellation ? 'Создана' : 'Подтверждена']);
        default:
            throw new RuntimeException('Unexpected Ozon operation: ' . $path);
    }
};

$yookassa = static function (RequestInterface $request) use ($reply, &$providerPayments, &$providerReceipts, &$providerRefunds, &$refundCalls) {
    $path = $request->getUri()->getPath();
    if ($request->getMethod() === 'POST') {
        $body = json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $operation = $request->getHeaderLine('Idempotence-Key');
        check(strlen($operation) >= 32 && strlen($operation) <= 64, 'YooKassa operation requires stable idempotency key');
        $id = substr($operation, 0, 32);
        if ($path === '/v3/payments') {
            check(($body['receipt']['items'][0]['payment_mode'] ?? null) === 'full_prepayment', 'advance receipt accompanies payment');
            $providerPayments[$id] ??= $body + ['id' => $id, 'status' => 'pending', 'paid' => false, 'test' => false];
            return $reply($providerPayments[$id]);
        }
        if ($path === '/v3/receipts') {
            check($body['items'][0]['payment_mode'] === 'full_payment', 'settlement receipt after handover');
            $providerReceipts[$id] ??= $body + ['id' => $id, 'status' => 'succeeded'];
            return $reply($providerReceipts[$id]);
        }
        if ($path === '/v3/refunds') {
            ++$refundCalls;
            $providerRefunds[$id] ??= $body + ['id' => $id, 'status' => 'succeeded'];
            return $reply($providerRefunds[$id]);
        }
    }
    $id = basename($path);
    $collection = explode('/', trim($path, '/'))[1];
    $value = match ($collection) {
        'payments' => $providerPayments[$id] ?? null,
        'receipts' => $providerReceipts[$id] ?? null,
        'refunds' => $providerRefunds[$id] ?? null,
    };
    check($value !== null, 'unknown YooKassa operation ' . $path);
    return $reply($value);
};

$db->beginTransaction();
try {
    foreach (['cart_enabled' => true, 'testing_enabled' => false, 'promo_codes_enabled' => true, 'order_emails_enabled' => false, 'bitrix_crm_enabled' => false] as $key => $value) {
        $db->executeStatement('INSERT INTO site_settings (`key`, value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$key, json_encode($value)]);
    }
    $now = gmdate('Y-m-d H:i:s');
    $db->insert('users', ['role' => UserRole::USER->value, 'first_name' => 'Тестовый', 'last_name' => 'Покупатель',
        'email' => $email, 'status' => 1, 'password' => 'unused', 'created_at' => $now]);
    $user = (int)$db->lastInsertId();
    $db->insert('user_profiles', [
        'user_id' => $user, 'phone' => '+79000000000', 'verified_phone' => '79000000000', 'phone_verified_at' => $now,
        'referral_code' => 'ozon-test-buyer', 'created_at' => $now,
    ]);
    $db->insert('products', [
        'id' => 1, 'slug' => 'ozon-test-product', 'name' => 'Товар Ozon', 'category_id' => '1', 'price' => 10,
        'is_active' => 1, 'availability' => 'in_stock', 'legal_status' => 'bad',
        'weight' => '', 'image' => '', 'description' => '', 'created_at' => $now,
    ]);
    $db->insert('ozon_catalog_products', ['ozon_id' => '1000123457', 'product_id' => 1,
        'sku' => (string)$sku, 'fbo_sku' => (string)$sku, 'stock' => 1000, 'archived' => 0]);
    $db->insert('ozon_catalog_state', ['id' => 1]);
    $scopes = ['seller-api.product-read-only', 'seller-api.ozon-logistics', 'seller-api.posting-fbo', 'seller-api.returns', 'seller-api.posting-fbs'];
    $token = 'fixture.' . rtrim(strtr(base64_encode(json_encode(['sub' => '123', 'scope' => $scopes])), '+/', '-_'), '=') . '.unused';
    $container->get(IntegrationCredentialStore::class)->save('ozon.seller.oauth', json_encode([
        'seller' => '123', 'client' => 'fixture', 'access' => $token, 'refresh' => 'fixture', 'expires' => time() + 3600,
    ]));
    $container->set(OzonApi::class, new OzonApi(new Client(['handler' => $ozon])));
    $container->set(YooKassaGateway::class, new YooKassaGateway(new Client(['handler' => $yookassa]), [
        'shopId' => 'fixture', 'secret' => 'fixture', 'returnBase' => 'https://example.test',
        'receipts' => true, 'vatCode' => 1, 'ffd12' => true, 'timezone' => 6,
    ]));
    $container->set(SmsAero::class, new SmsAero(new Client(['handler' => static function (): never {
        throw new RuntimeException('An already verified phone must not send another SMS.');
    }]), 'fixture@example.test', 'fixture', 'BIOFARM', 'https://example.test/webhooks/smsaero/mobile-id'));
    $app = (require dirname(__DIR__) . '/config/app.php')($container);
    $jwt = $container->get(JwtTokenService::class);
    $buyer = $jwt->generateAccessToken(AccessTokenPayload::issue($user, 'Тестовый покупатель', UserRole::USER, 900));
    $db->insert('users', ['role' => UserRole::ADMIN->value, 'first_name' => 'Тестовый', 'last_name' => 'Администратор',
        'email' => 'ozon-admin-' . bin2hex(random_bytes(6)) . '@example.test', 'status' => 1,
        'password' => 'unused', 'created_at' => gmdate('Y-m-d H:i:s')]);
    $admin = $jwt->generateAccessToken(AccessTokenPayload::issue((int)$db->lastInsertId(), 'Тестовый администратор', UserRole::ADMIN, 900));
    $call = static function (string $method, string $path, int $expected, array $payload = [], ?string $auth = null, ?string $key = null) use ($app, $container) {
        $container->get(EntityManagerInterface::class)->clear();
        $request = new ServerRequestFactory()->createServerRequest($method, 'http://localhost:8094' . $path)
            ->withHeader('Accept', 'application/json')->withParsedBody($payload);
        if ($auth !== null) $request = $request->withHeader('Authorization', 'Bearer ' . $auth);
        if ($key !== null) $request = $request->withHeader('Idempotency-Key', $key);
        $response = $app->handle($request);
        $raw = (string)$response->getBody();
        check($response->getStatusCode() === $expected, "$method $path returned {$response->getStatusCode()}: " . substr($raw, 0, 330));
        $json = json_decode($raw, true);
        return $json['data'] ?? $json;
    };
    $prospective = $container->get(OzonConnection::class)->authorizeUrl();
    parse_str(parse_url($prospective, PHP_URL_QUERY), $query);
    check(explode(' ', $query['scope']) === $scopes, 'request correct Seller scopes');
    check($container->get(OzonPickupDirectory::class)->refresh() === 1, 'refreshes Ozon directory');
    check($container->get(OzonPickupDirectory::class)->refresh(onlyIfDue: true) === 0, 'a completed pickup directory is reused');
    $points = $call('GET', '/v1/ozon-delivery/pickup-points?q=' . rawurlencode('Томск Ленина'), 200);
    check(($points[0]['id'] ?? null) === $point, 'local address search returns matching point');
    $fulfillment = $container->get(OzonFulfillment::class);
    $payments = $container->get(PaymentService::class);
    $qty = ['items' => [['productId' => 1, 'quantity' => 2]]];
    $quoteInput = $qty + [
        'method' => 'ozon-pickup', 'phone' => '+79000000000', 'pickupPointId' => $point,
        'personalDataConsent' => true, 'consentVersion' => ConsentRecorder::VERSION,
    ];
    $db->update('user_profiles', ['phone_verification_sandbox' => 1], ['user_id' => $user]);
    $call('POST', '/v1/ozon-delivery/quote', 422, $quoteInput, $buyer);
    $db->update('user_profiles', ['phone_verification_sandbox' => 0], ['user_id' => $user]);
    $quote = $call('POST', '/v1/ozon-delivery/quote', 200, $quoteInput, $buyer);
    check($quote['deliveryCost'] === 30 && $quote['dates'] === [$date], 'actual calculated cost and date returned');
    $body = $qty + [
        'deliveryMethod' => 'ozon-pickup', 'paymentMethod' => 'card', 'rememberAddress' => true,
        'shippingAddress' => ['phone' => '+79000000000', 'email' => $email, 'name' => 'Тестовый покупатель'],
        'ozonPickupPointId' => $point, 'ozonQuote' => $quote['fingerprint'],
        'offerAccepted' => true, 'offerVersion' => ConsentRecorder::OFFER_VERSION,
    ];
    $newOrder = static function (array $overrides = []) use ($call, $body, $buyer, $db): string {
        $key = bin2hex(random_bytes(20));
        $request = $overrides + $body;
        $order = $call('POST', '/v1/orders/create', 201, $request, $buyer, $key);
        check($call('POST', '/v1/orders/create', 201, $request, $buyer, $key)['id'] === $order['id'], 'repeat create returns same local order');
        check($db->fetchOne('SELECT status FROM ozon_delivery_orders WHERE order_id=?', [$order['id']]) === 'awaiting_payment', 'unpaid local draft');
        return $order['id'];
    };
    $pay = static function (string $order) use ($call, $buyer, $fulfillment, &$providerPayments, &$createCalls): void {
        $before = $createCalls;
        $fulfillment->process($order);
        check($createCalls === $before, 'must not dispatch unpaid order');
        $call('POST', "/v1/payments/$order", 200, [], $buyer);
        $id = array_key_last($providerPayments);
        $notification = ['event' => 'payment.succeeded', 'object' => ['id' => $id, 'status' => 'succeeded']];
        $call('POST', '/webhooks/yookassa', 200, $notification);
        $fulfillment->process($order);
        check($createCalls === $before, 'webhook claim alone must not trigger dispatch');
        $providerPayments[$id]['status'] = 'succeeded';
        $providerPayments[$id]['paid'] = true;
        $call('POST', '/webhooks/yookassa', 200, $notification);
        $call('POST', '/webhooks/yookassa', 200, $notification);
        $fulfillment->process($order);
        check($createCalls === $before + 1, 'exactly one dispatch after verified payment');
    };

    $id = $newOrder();
    $before = $createCalls;
    $call('POST', "/v1/payments/$id", 200, [], $buyer);
    $providerId = array_key_last($providerPayments);
    $providerPayments[$providerId] = array_replace($providerPayments[$providerId], ['status' => 'succeeded', 'paid' => true, 'test' => true]);
    $call('POST', '/webhooks/yookassa', 503, ['event' => 'payment.succeeded', 'object' => ['id' => $providerId]]);
    $fulfillment->process($id);
    check($createCalls === $before && $db->fetchOne('SELECT payment_status FROM orders WHERE id=?', [$id]) === 'pending',
        'A test payment must never settle the order or dispatch it to Ozon');
    $id = $newOrder();
    $pay($id);
    $fulfillment->process($id);
    $tracking = $call('GET', "/v1/ozon-delivery/orders/$id/tracking", 200, auth: $buyer);
    check($tracking[0]['substatus'] === 'posting_in_pickup_point', 'shows pickup status');
    check($db->fetchOne('SELECT delivered_at FROM program_orders WHERE id=?', [$id]) === null, 'arrival at pickup does not confirm handover');
    $pickedStatus = 'delivered';
    $fulfillment->process($id);
    check($db->fetchOne('SELECT status FROM orders WHERE id=?', [$id]) === 'delivered', 'Ozon handover reconciles order');
    check($payments->settlementReceipt($id)['status'] === 'succeeded', 'settlement receipt accepted');
    $fiscal = end($providerReceipts);
    check(count($fiscal['items']) === 3 && $fiscal['items'][0]['mark_code_info']['gs_1m'] === base64_encode($nativeMarks[0]), 'one receipt line per issued mark');
    $itemId = (int)$db->fetchOne('SELECT id FROM order_items WHERE order_id=?', [$id]);
    $refund = ['requestId' => 'refund-verified-0001', 'items' => [['itemId' => $itemId, 'quantity' => 1, 'markCodes' => [$nativeMarks[0]]]]];
    check($call('POST', "/admin/api/payments/orders/$id/refund", 200, $refund, $admin)['status'] === 'succeeded', 'refund after receipt');
    check(end($providerRefunds)['receipt']['items'][0]['mark_code_info']['gs_1m'] === base64_encode($nativeMarks[0]), 'refund uses precisely the returned instance code');
    $repeat = $refund;
    $repeat['requestId'] = 'refund-verified-0002';
    $call('POST', "/admin/api/payments/orders/$id/refund", 422, $repeat, $admin);

    $cancelId = $newOrder();
    $pay($cancelId);
    $cancelItem = (int)$db->fetchOne('SELECT id FROM order_items WHERE order_id=?', [$cancelId]);
    $call('POST', "/admin/api/payments/orders/$cancelId/refund", 200, ['items' => [['itemId' => $cancelItem,'quantity' => 2]], 'refundDelivery' => true], $admin);
    $beforeRefund = $refundCalls;
    $fulfillment->process($cancelId);
    $fulfillment->process($cancelId);
    check($refundCalls === $beforeRefund, 'pending Ozon cancellation must not refund');
    $pendingCancellation = false;
    $fulfillment->process($cancelId);
    $fulfillment->process($cancelId);
    $fulfillment->process($cancelId);
    check($refundCalls === $beforeRefund + 1, 'confirmed cancellation refunds only once');
    check($db->fetchOne('SELECT payment_status FROM orders WHERE id=?', [$cancelId]) === 'refunded', 'cancelled order is actually refunded');

    $partial = true;
    $partialId = $newOrder();
    $pay($partialId);
    $fulfillment->process($partialId);
    $fulfillment->process($partialId);
    check($payments->settlementReceipt($partialId)['status'] === 'succeeded', 'partial handover receipt');
    check(count(end($providerReceipts)['items']) === 2 && end($providerRefunds)['amount']['value'] === '10.00', 'only the issued item is fiscalized and one item is refunded');
    $partial = false;

    $db->update('ozon_catalog_products', ['stock' => 0, 'fbs_sku' => (string)$sku, 'fbs_stock' => 2], ['product_id' => 1]);
    $fbsQuote = $call('POST', '/v1/ozon-delivery/quote', 200, $quoteInput, $buyer);
    check(array_column($fbsQuote['shipments'], 'schema') === ['FBS'], 'FBS-only delivery');
    $fbsId = $newOrder(['ozonQuote' => $fbsQuote['fingerprint']]);
    $call('POST', '/v1/ozon-delivery/quote', 422, $quoteInput, $buyer);
    $pay($fbsId);
    $fulfillment->process($fbsId);
    check($payments->settlementReceipt($fbsId)['status'] === 'succeeded', 'FBS-only handover issues a marked receipt');

    $fbsRefusal = true;
    $fbsPartial = $newOrder(['ozonQuote' => $fbsQuote['fingerprint']]);
    $pay($fbsPartial);
    $beforeReceipts = count($providerReceipts);
    $fulfillment->process($fbsPartial);
    check($db->fetchOne('SELECT status FROM ozon_delivery_orders WHERE order_id=?', [$fbsPartial]) === 'submitted'
        && count($providerReceipts) === $beforeReceipts, 'ambiguous FBS refusal waits for exact exemplar IDs');
    $resolution = ['posting' => array_key_last($created) . '-1', 'reason' => 'Сверено с экземплярами возврата у продавца.'];
    $call('POST', "/admin/api/payments/orders/$fbsPartial/fbs-refusal", 422, ['ids' => ['9999']] + $resolution, $admin);
    $call('POST', "/admin/api/payments/orders/$fbsPartial/fbs-refusal", 200, ['ids' => ['1']] + $resolution, $admin);
    $fbsRefusalIdentified = true;
    $fulfillment->process($fbsPartial);
    $fulfillment->process($fbsPartial);
    check($payments->settlementReceipt($fbsPartial)['status'] === 'succeeded'
        && end($providerReceipts)['items'][0]['mark_code_info']['gs_1m'] === base64_encode($nativeMarks[1]), 'partial FBS receipt uses only the issued mark');
    $fbsRefusal = false;
    $db->update('ozon_catalog_products', ['stock' => 1000], ['product_id' => 1]);
    $splitCheckout = true;
    $splitItems = [['productId' => 1, 'quantity' => 3]];
    $quote = $call('POST', '/v1/ozon-delivery/quote', 200, [
        'items' => $splitItems, 'method' => 'ozon-pickup', 'phone' => '+79000000000', 'pickupPointId' => $point,
        'personalDataConsent' => true, 'consentVersion' => ConsentRecorder::VERSION,
    ], $buyer);
    check($quote['deliveryCost'] === 30 && $quote['dates'] === [$date, '2026-12-02T10:00:00Z'], 'multi-posting quotes sum fees and list both dates');
    $promo = 'SPLIT-' . strtoupper(bin2hex(random_bytes(6)));
    $call('POST', '/admin/api/promo-codes', 201, ['code' => $promo, 'type' => 'fixed', 'value' => 1], $admin);
    $splitOrder = ['items' => $splitItems, 'ozonQuote' => $quote['fingerprint'], 'promoCode' => $promo];

    foreach (['delivered', 'cancelled'] as $secondStatus) {
        $id = $newOrder($splitOrder);
        $paymentsBefore = count($providerPayments);
        $receiptsBefore = count($providerReceipts);
        $refundsBefore = $refundCalls;
        $pay($id);
        $num = array_key_last($created);
        check(count($providerPayments) === $paymentsBefore + 1 && count($created[$num]['splits']) === 2, 'one checkout creates one payment and one Ozon order containing both splits');
        check(end($providerPayments)['amount']['value'] === '59.00', 'one payment includes the cart discount and both delivery charges');
        check($payments->settlementReceipt($id)['status'] === 'not_required', 'no receipt before the first handover');
        $postingStatuses[$num . '-1'] = 'delivered';
        $postingStatuses[$num . '-2'] = 'unlisted';
        $fbsTrackingOffline = true;
        $fulfillment->process($id);
        $fulfillment->process($id);
        check($payments->settlementReceipt($id)['status'] === 'succeeded', 'first handover can be fiscalized before Ozon lists the second posting');
        check(count($providerReceipts) === $receiptsBefore + 1 && $refundCalls === $refundsBefore, 'incomplete list neither duplicates a receipt nor refunds missing goods');
        check(end($providerReceipts)['settlements'][0]['amount']['value'] === '39.67', 'first receipt includes the issued unit and the delivery fee once');
        check($db->fetchOne('SELECT status FROM orders WHERE id=?', [$id]) === 'processing', 'order is still processing after the first handover');
        $fbsTrackingOffline = false;
        $postingStatuses[$num . '-2'] = $secondStatus;
        $fulfillment->process($id);
        $fulfillment->process($id);
        check($payments->settlementReceipt($id)['status'] === 'succeeded', 'all issued shipments have receipts');
        $tracking = $call('GET', "/v1/ozon-delivery/orders/$id/tracking", 200, auth: $buyer);
        check(count($tracking) === 2 && in_array($secondStatus, array_column($tracking, 'status'), true), 'one order exposes both postings with their statuses');
        if ($secondStatus === 'delivered') {
            check(count($providerReceipts) === $receiptsBefore + 2 && $refundCalls === $refundsBefore, 'two handovers produce two receipts without refunds');
            check(end($providerReceipts)['settlements'][0]['amount']['value'] === '19.33', 'second receipt settles the remaining goods without charging delivery again');
        } else {
            check(count($providerReceipts) === $receiptsBefore + 1 && $refundCalls === $refundsBefore + 1, 'canceling the second posting only refunds its items');
            check(end($providerRefunds)['amount']['value'] === '19.33' && end($providerRefunds)['receipt']['items'][0]['payment_mode'] === 'full_prepayment', 'unissued goods refund the outstanding advance');
        }
        $history = $call('GET', "/admin/api/payments/orders/$id", 200, auth: $admin);
        check(in_array($num . '-1', array_column($history['operations'], 'posting_number'), true), 'posting receipt can be identified in the admin history');
        $itemId = (int)$db->fetchOne('SELECT id FROM order_items WHERE order_id=?', [$id]);
        $return = ['requestId' => 'return-first-issued-' . $secondStatus, 'items' => [['itemId' => $itemId, 'quantity' => 1, 'markCodes' => [$nativeMarks[0]]]]];
        $call('POST', "/admin/api/payments/orders/$id/refund", 200, $return, $admin);
        check(end($providerRefunds)['amount']['value'] === '9.67'
            && end($providerRefunds)['receipt']['items'][0]['mark_code_info']['gs_1m'] === base64_encode($nativeMarks[0]),
            'returning a selected marked item refunds the exact amount from its delivery receipt');
    }
    $splitCheckout = false;

    $db->update('ozon_catalog_products', ['fbs_sku' => null, 'fbs_stock' => 0], ['product_id' => 1]);
    $uncertain = $newOrder();
    $call('POST', "/v1/payments/$uncertain", 200, [], $buyer);
    $number = array_key_last($providerPayments);
    $providerPayments[$number]['paid'] = true;
    $providerPayments[$number]['status'] = 'succeeded';
    $call('POST', '/webhooks/yookassa', 200, ['event' => 'payment.succeeded', 'object' => ['id' => $number]]);
    $before = $createCalls;
    $unknownQuote = true;
    $fulfillment->process($uncertain);
    check($createCalls === $before && $db->fetchOne('SELECT status FROM ozon_delivery_orders WHERE order_id=?', [$uncertain]) === 'queued',
        'unknown availability after payment must be retried without dispatch or refund');
    $unknownQuote = false;
    $fulfillment->process($uncertain);
    check($createCalls === $before + 1, 'dispatch after the paid quote is confirmed');
    $late = $newOrder();
    $call('POST', "/v1/payments/$late", 200, [], $buyer);
    $latePayment = array_key_last($providerPayments);
    $db->update('ozon_stock_reservations', ['expires_at' => '2000-01-01'], ['order_id' => $late]);
    $call('POST', "/v1/payments/$late", 409, [], $buyer);
    $providerPayments[$latePayment]['paid'] = true;
    $providerPayments[$latePayment]['status'] = 'succeeded';
    $call('POST', '/webhooks/yookassa', 200, ['event' => 'payment.succeeded', 'object' => ['id' => $latePayment]]);
    $before = $createCalls;
    $fulfillment->process($late);
    $fulfillment->process($late);
    check($db->fetchOne('SELECT payment_status FROM orders WHERE id=?', [$late]) === 'refunded' && $createCalls === $before,
        'late payment is refunded without dispatch');
    $fixture = $db->fetchAssociative('SELECT * FROM products WHERE id=1');
    $db->insert('products', array_replace($fixture, ['id' => 2, 'slug' => 'ozon-fbs-item', 'sku' => 'second-fbs']));
    $db->insert('ozon_catalog_products', ['ozon_id' => '1000123458', 'product_id' => 2, 'sku' => $sku + 100,
        'fbs_sku' => $sku + 101, 'stock' => 0, 'fbs_stock' => 4, 'archived' => 0]);
    $basket = [['productId' => 1, 'quantity' => 1], ['productId' => 2, 'quantity' => 1]];
    $quote2 = $call('POST', '/v1/ozon-delivery/quote', 200, ['items' => $basket] + $quoteInput, $buyer);
    $two = $newOrder(['items' => $basket, 'ozonQuote' => $quote2['fingerprint']]);
    check((int)$db->fetchOne('SELECT COUNT(*) FROM ozon_stock_reservations WHERE order_id=?', [$two]) === 2,
        'mixed cart reserves both products using their scheme-specific SKUs');
    $lostCreate = true;
    $reviewId = $newOrder();
    $pay($reviewId);
    $before = $createCalls;
    $fulfillment->process($reviewId);
    check($createCalls === $before, 'unknown create result must not be retried');
    check($db->fetchOne('SELECT status FROM ozon_delivery_orders WHERE order_id=?', [$reviewId]) === 'submit_review', 'unknown result queued for operator review');
    $before = $refundCalls;
    $call('POST', "/admin/api/payments/orders/$reviewId/ozon-resolution", 200,
        ['confirmedMissing' => true, 'reason' => 'Проверили историю заказов у продавца.'], $admin);
    $fulfillment->process($reviewId);
    check($refundCalls === $before + 1, 'operator-confirmed missing order refunds once');
    echo "PASS Ozon Delivery HTTP: one payment for several shipments, marked receipts per handover, partial refunds, cancellation and retries. Providers simulated.\n";
} finally {
    $db->rollBack();
}
