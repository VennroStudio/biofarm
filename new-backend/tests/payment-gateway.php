<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Payment\Service\YooKassaGateway;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

$history = [];
$stack = HandlerStack::create(new MockHandler([
    new Response(200, [], '{"id":"payment-1","status":"pending"}'),
    new Response(200, [], '{"id":"payment-1","status":"succeeded"}'),
    new Response(200, [], '{"id":"refund-1","status":"pending"}'),
]));
$stack->push(Middleware::history($history));
$gateway = new YooKassaGateway(new Client(['handler' => $stack]), ['shopId' => 'test-shop', 'secret' => 'test-secret', 'returnBase' => 'https://example.test']);
$assert = static function (bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } };
$gateway->createPayment(['amount' => ['value' => '30.50', 'currency' => 'RUB']], 'stable-key');
$verified = $gateway->payment('payment-1');
$gateway->createRefund(['payment_id' => 'payment-1', 'amount' => ['value' => '10.00', 'currency' => 'RUB']], 'refund-key');
$assert($history[0]['request']->getHeaderLine('Idempotence-Key') === 'stable-key', 'Stable payment idempotence key');
$assert($history[1]['request']->getMethod() === 'GET', 'Verification fetches authoritative payment');
$assert($verified['status'] === 'succeeded', 'Verified provider status');
$assert((string)$history[2]['request']->getUri() === 'https://api.yookassa.ru/v3/refunds', 'Refund endpoint');
$assert(YooKassaGateway::money(3050) === '30.50', 'Minor units formatted exactly');
$assert(YooKassaGateway::minor('30.50') === 3050, 'Provider decimal parsed without floats');
try {
    YooKassaGateway::minor('1e3');
    throw new RuntimeException('Accepted malformed money');
} catch (InvalidArgumentException) {
}
try {
    new YooKassaGateway(null, ['shopId' => '', 'secret' => ''])->payment('x');
    throw new RuntimeException('Unconfigured provider accepted');
} catch (DomainExceptionModule $e) {
    $assert($e->getStatus() === 503, 'Missing configuration visible');
}
// Site domain and the YooKassa return path are configured separately.
$_ENV['YOOKASSA_SHOP_ID'] = 'fixture-shop';
$_ENV['YOOKASSA_SECRET_KEY'] = 'fixture-secret';
$_ENV['FRONTEND_URL'] = 'https://shop.example.test/';
$_ENV['YOOKASSA_RETURN_BASE_URL'] = '/payment-result';
$siteGateway = new YooKassaGateway();
$assert($siteGateway->configured(), 'Site URL completes gateway configuration');
$assert($siteGateway->returnUrl('order /?') === 'https://shop.example.test/payment-result?order=order%20%2F%3F', 'Return stays on configured site, order ID is encoded');
$_ENV['YOOKASSA_RETURN_BASE_URL'] = 'order-success';
$assert(new YooKassaGateway()->returnUrl('1') === 'https://shop.example.test/order-success?order=1', 'Path without leading slash is joined once');
$_ENV['YOOKASSA_RETURN_BASE_URL'] = '';
$assert(new YooKassaGateway()->returnUrl('1') === 'https://shop.example.test/order-success?order=1', 'Empty return path defaults to the existing page');
foreach (['https://other.example.test', '//other.example.test', '/order-success?order=other', '/order-success#fragment'] as $invalidPath) {
    $_ENV['YOOKASSA_RETURN_BASE_URL'] = $invalidPath;
    $assert(!new YooKassaGateway()->configured(), 'Return path must not override the site domain or order query');
}
$_ENV['YOOKASSA_RETURN_BASE_URL'] = '/order-success';
$_ENV['FRONTEND_URL'] = '';
$_ENV['APP_URL'] = 'https://fallback.example.test';
$assert(new YooKassaGateway()->returnUrl('1') === 'https://fallback.example.test/order-success?order=1', 'App URL fallback when frontend URL is empty');
$_ENV['FRONTEND_URL'] = 'javascript:alert(1)';
$assert(!new YooKassaGateway()->configured(), 'Invalid site URL cannot configure payment redirect');
echo "PASS payment gateway: request keys, verification, refund, minor amounts, configuration\n";
