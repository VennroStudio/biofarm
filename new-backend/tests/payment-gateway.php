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
echo "PASS payment gateway: request keys, verification, refund, minor amounts, configuration\n";
