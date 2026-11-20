<?php

declare(strict_types=1);

use App\Components\Auth\AccessTokenPayload;
use App\Components\Auth\JwtTokenService;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Integration\Credential\IntegrationCredentialStore;
use App\Components\Setting\SiteSettings;
use App\Modules\Order\Command\Order\Create\CreateOrderCommand;
use App\Modules\Order\Command\Order\Create\CreateOrderHandler;
use App\Modules\Ozon\OzonApi;
use App\Modules\Ozon\OzonCatalogSync;
use App\Modules\Ozon\OzonPickupCheckout;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/ozon-test-tables.php';

$secret = str_repeat('a', 32);
putenv('OZON_CLIENT_ID=fixture');
putenv('OZON_CLIENT_SECRET=fixture');
putenv('OZON_SELLER_ID=123');
putenv('OZON_REDIRECT_URI=http://localhost:8088/ozon/callback');
putenv('OZON_WEBHOOK_SECRET=' . $secret);
putenv('OZON_DELIVERY_ENABLED=1');
putenv('CHECKOUT_PHONE_VERIFICATION_ENABLED=1');
putenv('SMSAERO_EMAIL=sender@example.test');
putenv('SMSAERO_API_KEY=fixture');
putenv('SMSAERO_SIGN=BIOFARM');
putenv('SMSAERO_CALLBACK_URL=https://example.test/webhooks/smsaero/mobile-id');
putenv('REDIS=false');

$container = require dirname(__DIR__) . '/config/container.php';
$db = $container->get(Connection::class);
createOzonTestTables($db, [
    'site_settings', 'categories', 'products', 'product_images', 'product_group_items', 'product_groups',
    'ozon_catalog_products', 'ozon_catalog_groups', 'ozon_catalog_state', 'ozon_stock_queue',
    'integration_credentials', 'integration_error_logs', 'users',
]);
putenv('APP_ENV=prod');
$db->insert('ozon_catalog_state', ['id' => 1]);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function detail(int $id, int $model, int $stock): array
{
    return [
        'id' => $id,
        'offer_id' => 'fixture-' . $id,
        'name' => 'Экстракт ' . $id . ' 100 мл',
        'model_info' => ['model_id' => $model],
        'price' => '900.00',
        'old_price' => '1100.00',
        'currency_code' => 'RUB',
        'sku' => $id + 1000,
        'sources' => [['source' => 'fbo', 'sku' => $id + 2000]],
        'stocks' => ['stocks' => [
            ['source' => 'fbo', 'sku' => $id + 1000, 'present' => $stock + 2, 'reserved' => 2],
            ['source' => 'fbs', 'sku' => $id + 1000, 'present' => 100, 'reserved' => 0],
        ]],
        'primary_image' => ['https://ir.ozone.ru/s3/example.jpg'],
        'images' => ['https://ir.ozone.ru/s3/example.jpg', 'https://ir.ozone.ru/s3/second.jpg'],
        'is_archived' => false,
    ];
}

$details = [901 => detail(901, 77, 3), 902 => detail(902, 77, 0)];
$listing = [901, 902];
$failDetails = false;
$oauthCalls = [];
$listCalls = 0;
$handler = static function (RequestInterface $request) use (&$details, &$listing, &$failDetails, &$oauthCalls, &$listCalls) {
    $path = $request->getUri()->getPath();
    $body = (string) $request->getBody();
    if ($path === '/oauth/token') {
        parse_str($body, $form);
        $oauthCalls[] = $form;
        $expiresAt = time() + (count($oauthCalls) === 1 ? 60 : 3600);
        $claims = json_encode(['sub' => '123', 'exp' => $expiresAt], JSON_THROW_ON_ERROR);
        $reply = [
            'access_token' => 'fixture.' . rtrim(strtr(base64_encode($claims), '+/', '-_'), '=') . '.fixture',
            'refresh_token' => 'fixture-refresh-' . count($oauthCalls),
            'expires_in' => $expiresAt,
        ];
    } else {
        check(str_starts_with($request->getHeaderLine('Authorization'), 'Bearer fixture.'), 'Seller API must use the OAuth token.');
        $input = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        $reply = match ($path) {
            '/v3/product/list' => (static function () use ($input, $listing, $details, &$listCalls): array {
                ++$listCalls;
                $offset = $input['last_id'] === '' ? 0 : (int) $input['last_id'];
                $rows = array_map(static fn ($id) => ['product_id' => $id, 'archived' => $details[$id]['is_archived']], array_slice($listing, $offset, 1));
                return ['result' => ['items' => $rows, 'total' => count($listing), 'last_id' => (string) ($offset + 1)]];
            })(),
            '/v3/product/info/list' => ['items' => $failDetails ? [] : array_values(array_intersect_key($details, array_fill_keys($input['product_id'], true)))],
            '/v1/product/info/description' => ['result' => ['id' => $input['product_id'], 'description' => '<p>Ozon &amp; Biofarm</p><p>&lt;script&gt;alert(1)&lt;/script&gt;</p>']],
            default => throw new RuntimeException('Unexpected API operation.'),
        };
    }
    return Create::promiseFor(new Response(200, ['Content-Type' => 'application/json'], json_encode($reply, JSON_THROW_ON_ERROR)));
};
$ozonApi = new OzonApi(new Client(['handler' => $handler]));
$container->set(OzonApi::class, $ozonApi);
$app = (require dirname(__DIR__) . '/config/app.php')($container);

$now = gmdate('Y-m-d H:i:s');
$db->executeStatement("INSERT INTO site_settings (`key`, value) VALUES ('cart_enabled', 'true') ON DUPLICATE KEY UPDATE value = 'true'");
foreach (['kapsuly', 'ekstrakty-i-kontsentraty', 'produkciya'] as $slug) {
    $db->insert('categories', ['slug' => $slug, 'name' => $slug, 'created_at' => $now]);
}
$db->insert('products', [
    'slug' => 'old-product', 'name' => 'Старый товар', 'category_id' => '1',
    'price' => 100, 'image' => '', 'weight' => '', 'description' => '', 'created_at' => $now,
]);
foreach ([UserRole::ADMIN, UserRole::EDITOR] as $role) {
    $db->insert('users', [
        'id' => $role->value, 'role' => $role->value, 'first_name' => 'Ozon', 'last_name' => 'Fixture',
        'email' => $role->name . '@example.test', 'password' => 'unused', 'status' => 1, 'created_at' => $now,
    ]);
}
$token = static fn (UserRole $role) => $container->get(JwtTokenService::class)->generateAccessToken(AccessTokenPayload::issue($role->value, 'Ozon', $role, 300));
$call = static function (string $method, string $path, int $expected, ?string $auth = null, ?array $body = null) use (&$app) {
    $request = (new ServerRequestFactory())->createServerRequest($method, 'http://localhost:8088' . $path);
    if ($auth !== null) {
        $request = $request->withHeader('Authorization', 'Bearer ' . $auth);
    }
    if ($body !== null) {
        $request = $request->withHeader('Content-Type', 'application/json')->withParsedBody($body);
    }
    $response = $app->handle($request);
    check($response->getStatusCode() === $expected, "$method $path expected $expected, got " . $response->getStatusCode());
    return $response;
};
$data = static fn ($response) => json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR)['data'];
$admin = $token(UserRole::ADMIN);
$call('PATCH', '/admin/api/settings', 401, body: ['testing_enabled' => true]);
check($data($call('PATCH', '/admin/api/settings', 200, $admin, ['testing_enabled' => true]))['testing_enabled'] === true,
    'Production admin can save testing mode');
check($container->get(SiteSettings::class)->bool('testing_enabled'), 'Run the Ozon catalog scenarios with testing enabled.');
check(!$container->get(SiteSettings::class)->simulatesCheckoutPayments(), 'Production never simulates payment in testing mode.');
check($container->get(OzonPickupCheckout::class)->method() === null, 'Testing must disable Ozon checkout even when Ozon delivery is configured.');
$login = (string)$call('GET', '/login', 200)->getBody();
check(str_contains($login, 'data-sms-available="false"'), 'The login page disables SMS during testing.');
$call('GET', '/v1/ozon-delivery/pickup-points?q=Tomsk', 404);
$call('POST', '/v1/ozon-delivery/quote', 404);
foreach ($app->getRouteCollector()->getRoutes() as $route) {
    check(!str_starts_with($route->getPattern(), '/v1/ozon-delivery/sandbox/'), 'Delivery must not expose the old test routes.');
}
$phoneUnavailable = (string) $call('POST', '/v1/phone-login/start', 503)->getBody();
check(str_contains($phoneUnavailable, 'Подтверждение телефона по СМС временно недоступно.'), 'Testing must show the SMS availability message.');
$base = '/admin/api/integrations/ozon';
$call('POST', $base . '/sync', 401);
$call('POST', $base . '/sync', 403, $token(UserRole::EDITOR));
$call('POST', $base . '/sync', 422, $admin);
$authorize = $data($call('POST', $base . '/connect', 200, $admin));
parse_str((string) parse_url($authorize['url'], PHP_URL_QUERY), $parameters);
check($parameters['access_type'] === 'offline', 'A permanent integration must request offline authorization.');
$call('GET', '/ozon/callback?state=wrong&code=fixture', 400);
$callback = '/ozon/callback?state=' . $parameters['state'] . '&code=fixture';
$call('GET', $callback, 303);
$call('GET', $callback, 400);
check(count($oauthCalls) === 1, 'An OAuth state must be consumed once.');
$credentials = $container->get(IntegrationCredentialStore::class);
$tokens = json_decode($credentials->get('ozon.seller.oauth'), true, flags: JSON_THROW_ON_ERROR);
check($tokens['expires'] > time() && $tokens['expires'] <= time() + 60,
    'An absolute expires_in value must not extend the JWT expiration.');
$tokens['expires'] += time();
$credentials->save('ozon.seller.oauth', json_encode($tokens, JSON_THROW_ON_ERROR));

$sync = $container->get(OzonCatalogSync::class);
$zone = new DateTimeZone('Asia/Krasnoyarsk');
$clock = new DateTimeImmutable('now', $zone);
$failDetails = true;
$call('POST', $base . '/sync', 202, $admin);
check(!$sync->tick($clock), 'An incomplete initial catalog must fail.');
check($sync->status()['first'] && !$sync->status()['pending'] && $sync->status()['error'] !== null,
    'A failed import must report its error and allow another request.');
check((int) $db->fetchOne("SELECT COUNT(*) FROM products WHERE slug = 'old-product' AND deleted_at IS NULL") === 1,
    'An incomplete initial catalog must leave the old product intact.');
$failDetails = false;
$call('POST', $base . '/sync', 202, $admin);
check($sync->tick($clock), 'First sync must succeed.');
check($oauthCalls[1]['refresh_token'] === 'fixture-refresh-1', 'The worker must refresh an expiring JWT even with stale saved expiry.');
$result = $data($call('GET', $base, 200, $admin))['result'];
check($result === ['added' => 2, 'updated' => 0, 'archived' => 1, 'visible' => 2, 'available' => 1, 'groups' => 1, 'total' => 2], 'First replacement result.');
check(is_string($db->fetchOne("SELECT deleted_at FROM products WHERE slug = 'old-product'")), 'Legacy product must be archived and retained.');
$localId = $db->fetchOne('SELECT product_id FROM ozon_catalog_products WHERE ozon_id = 901');
$availabilityPath = '/admin/api/products/' . $localId . '/availability';
$call('PATCH', $availabilityPath, 401, body: ['availability' => 'out_of_stock']);
$call('PATCH', $availabilityPath, 200, $admin, ['availability' => 'out_of_stock']);
check((int) $db->fetchOne('SELECT is_active FROM products WHERE id = ?', [$localId]) === 1, 'The admin eye must preserve publication.');
$call('PATCH', $availabilityPath, 200, $admin, ['availability' => 'in_stock']);
$variantHtml = (string) $call('GET', '/product/ozon-901', 200)->getBody();
check(preg_match('~<a href="/product/ozon-902"[^>]*aria-label="[^"]*нет в наличии"[^>]*>\s*<img[^>]*grayscale~u', $variantHtml) === 1, 'The sibling variant must remain visible with a muted image and accessible stock status.');
check((int) $db->fetchOne('SELECT COUNT(*) FROM product_images WHERE product_id = ?', [$localId]) === 2, 'Images must be deduplicated and imported.');
check(!str_contains($db->fetchOne('SELECT description FROM products WHERE id = ?', [$localId]), '<script>'), 'Description must be sanitized.');

$call('POST', $base . '/sync', 422, $admin);

$manualPhoto = '/uploads/products/manual.jpg';
$db->update('products', [
    'image' => $manualPhoto, 'images' => json_encode([$manualPhoto], JSON_THROW_ON_ERROR), 'image_alt' => 'Фото редактора',
    'price' => 1200, 'old_price' => 1400, 'name' => 'Название редактора', 'description' => '<p>Описание редактора</p>',
    'category_id' => '1',
], ['id' => $localId]);
$db->delete('product_images', ['product_id' => $localId]);
$db->insert('product_images', [
    'product_id' => $localId, 'path' => $manualPhoto, 'alt' => 'Фото редактора', 'title' => 'Фото редактора',
    'sort_order' => 0, 'is_main' => 1, 'created_at' => $now,
]);
$readPhotos = static fn () => [
    $db->fetchAssociative('SELECT image, images, image_alt FROM products WHERE id = ?', [$localId]),
    $db->fetchAllAssociative('SELECT * FROM product_images WHERE product_id = ? ORDER BY id', [$localId]),
];
$manualPhotos = $readPhotos();
$readCard = static fn () => $db->fetchAssociative(
    'SELECT price, old_price, name, description, category_id, is_active, published_at, deleted_at FROM products WHERE id = ?',
    [$localId],
);
$manualCard = $readCard();
$groupId = $db->fetchOne('SELECT group_id FROM product_group_items WHERE product_id = ?', [$localId]);
$details[901]['primary_image'] = ['https://ir.ozone.ru/s3/new-cover.jpg'];
$details[901]['images'] = ['https://ir.ozone.ru/s3/new-gallery.jpg'];
$details[901]['price'] = '1000.00';
$details[901]['model_info']['model_id'] = 88;
$details[902]['is_archived'] = true;
$details[903] = detail(903, 88, 5);
$listing[] = 903;
$nextDay = $clock->modify('tomorrow')->setTime(3, 5);
$before = $listCalls;
$db->update('ozon_catalog_state', ['requested' => 1, 'error' => 'Old daily sync failed'], ['id' => 1]);
check($sync->tick($nextDay) && $before === $listCalls, 'A completed import must ignore the previous daily schedule and a stale request.');
check(!$sync->status()['pending'] && $sync->status()['error'] === null && $sync->status()['result'] === $result,
    'The original import result must remain visible without a stale request or error.');
check((int) $db->fetchOne('SELECT COUNT(*) FROM ozon_catalog_products WHERE ozon_id = 903') === 0,
    'A newly listed Ozon item must not be imported automatically.');
check($readCard() === $manualCard && $readPhotos() === $manualPhotos, 'The worker must preserve local edits after the import.');

$hook = '/webhooks/ozon/' . $secret;
$call('POST', '/webhooks/ozon/' . str_repeat('z', 32), 404, body: ['message_type' => 'TYPE_PING']);
$call('POST', $hook, 200, body: ['message_type' => 'TYPE_PING']);
$event = ['message_type' => 'TYPE_FBO_STOCKS_CHANGED', 'seller_id' => 123, 'sku' => 2901];
$call('POST', $hook, 200, body: array_replace($event, ['seller_id' => 456]));
check((int) $db->fetchOne('SELECT COUNT(*) FROM ozon_stock_queue') === 0, 'A foreign seller notification must be ignored.');
$details[901]['stocks']['stocks'][0]['present'] = 2;
$call('POST', $hook, 200, body: $event);
$call('POST', $hook, 200, body: $event);
check((int) $db->fetchOne('SELECT COUNT(*) FROM ozon_stock_queue') === 1, 'Identical webhooks must share a queue row.');
$details[902] = detail(902, 77, 5);
$call('POST', $hook, 200, body: array_replace($event, ['sku' => 2902]));
check($sync->tick($nextDay), 'Stock webhook must process successfully.');
$depleted = $db->fetchAssociative('SELECT is_active, availability FROM products WHERE id = ?', [$localId]);
check((int) $depleted['is_active'] === 1 && $depleted['availability'] === 'out_of_stock', 'Zero FBO stock must leave the product published.');
$call('PATCH', $availabilityPath, 409, $admin, ['availability' => 'in_stock']);
$unavailable = (string) $call('GET', '/product/ozon-901', 200)->getBody();
check(str_contains($unavailable, 'Нет в наличии') && !str_contains($unavailable, 'data-product-cart'), 'A direct link must show unavailable stock and hide the purchase button.');
$catalog = (string) $call('GET', '/catalog?sort=price-desc', 200)->getBody();
$availablePosition = strpos($catalog, '/product/ozon-902');
$soldOutPosition = strpos($catalog, '/product/ozon-901');
check($availablePosition !== false && $soldOutPosition !== false && $soldOutPosition > $availablePosition, 'A depleted card must move behind available stock, even when it has a higher price.');
check(str_contains($catalog, 'Нет в наличии'), 'The catalog must label depleted cards.');
try {
    $container->get(CreateOrderHandler::class)->handle(new CreateOrderCommand(
        userId: null,
        shippingAddress: [],
        paymentMethod: 'card',
        currentUserId: 1,
        currentUserRole: UserRole::ADMIN->value,
        items: [['productId' => $localId, 'quantity' => 1]],
    ), notify: false);
    throw new RuntimeException('Checkout accepted a published but depleted product.');
} catch (DomainExceptionModule $exception) {
    check($exception->getMessage() === 'error.order_product_not_available', 'Checkout must reject depleted stock.');
}

$details[901]['stocks']['stocks'][0]['present'] = 5;
$call('POST', $hook, 200, body: array_replace($event, ['sku' => 1901]));
check($sync->tick($nextDay), 'Restock webhook must process successfully.');
check($db->fetchOne('SELECT availability FROM products WHERE id = ?', [$localId]) === 'in_stock', 'A replenished product must become purchasable.');
check($readCard() === $manualCard && $readPhotos() === $manualPhotos, 'Stock webhooks must preserve manual prices, content, and images.');
check(str_contains((string) $call('GET', '/catalog', 200)->getBody(), '/product/ozon-901'), 'A replenished card must return to the catalog.');

$details[901]['is_archived'] = true;
$call('POST', $hook, 200, body: $event);
check($sync->tick($nextDay), 'An archive reported during a stock refresh must be handled as zero stock.');
check($db->fetchOne('SELECT availability FROM products WHERE id = ?', [$localId]) === 'out_of_stock'
    && $readCard() === $manualCard
    && $db->fetchOne('SELECT group_id FROM product_group_items WHERE product_id = ?', [$localId]) === $groupId,
    'An Ozon archive must leave the storefront card and its group intact.');
$call('GET', '/product/ozon-901', 200);

$details[901]['is_archived'] = false;
$details[901]['price'] = '1100.00';
$failDetails = true;
$call('POST', $hook, 200, body: $event);
check(!$sync->tick($nextDay), 'An incomplete stock response must fail.');
check((int) $db->fetchOne('SELECT attempts FROM ozon_stock_queue') === 1, 'The webhook must be queued for a retry.');
check($readCard() === $manualCard, 'An incomplete stock response must preserve local product content.');

check($data($call('PATCH', '/admin/api/settings', 200, $admin, ['testing_enabled' => false]))['testing_enabled'] === false,
    'Production admin can turn testing off');
$normal = require dirname(__DIR__) . '/config/container.php';
$normal->set(Connection::class, $db);
$normal->set(OzonApi::class, $ozonApi);
$app = (require dirname(__DIR__) . '/config/app.php')($normal);
check($normal->get(OzonPickupCheckout::class)->method() === OzonPickupCheckout::LIVE_PICKUP, 'Disabling testing enables the live pickup flow.');
$login = (string)$call('GET', '/login', 200)->getBody();
check(str_contains($login, 'data-sms-available="true"'), 'Disabling testing enables configured SMS verification.');
$failDetails = false;
$call('POST', $base . '/sync', 422, $admin);
$call('POST', $hook, 200, body: $event);
check($normal->get(OzonCatalogSync::class)->tick($nextDay), 'Stock webhooks also run with testing off.');
check($db->fetchOne('SELECT availability FROM products WHERE id = ?', [$localId]) === 'in_stock'
    && $readCard() === $manualCard && $before === $listCalls,
    'Stock can return after an Ozon archive without reimporting the catalog or changing the local price.');

echo "Ozon HTTP access, OAuth, one-time catalog import, preserved local prices and content, stock transitions and retries passed.\n";
