#!/usr/bin/env php
<?php

declare(strict_types=1);

// Persistent local demonstration. Successful orders and commissions remain visible in the UI.
use App\Components\Integration\Bitrix24\Bitrix24CrmSettings;
use App\Modules\Payment\Service\YooKassaGateway;
use App\Modules\Program\Service\ProgramService;
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

if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'dev') {
    throw new RuntimeException('This persistent demo is only available in local development.');
}
require dirname(__DIR__) . '/vendor/autoload.php';
$c = require dirname(__DIR__) . '/config/container.php';
$db = $c->get(Connection::class);
if ($c->get(Bitrix24CrmSettings::class)->ready()) {
    throw new RuntimeException('The demo requires an environment without an active CRM connection.');
}
$partner = (int)$db->fetchOne('SELECT p.user_id FROM user_profiles p JOIN users u ON u.id=p.user_id WHERE u.email=? AND p.is_partner=1 AND u.deleted_at IS NULL', ['demo-partner@biofarm.example.test']);
if (!$partner) throw new RuntimeException('The DEMO partner account is missing.');
$guestEmail = 'guest-visible-demo@biofarm.example.test';
$provider = [];
$c->set(YooKassaGateway::class, new YooKassaGateway(new Client(['handler' => static function (RequestInterface $r) use (&$provider) {
    if ($r->getMethod() === 'POST') {
        $id = 'demo-' . substr(hash('sha256', $r->getHeaderLine('Idempotence-Key')), 0, 40);
        $provider[$id] ??= array_merge(json_decode((string)$r->getBody(), true, 512, JSON_THROW_ON_ERROR), [
            'id' => $id, 'test' => true, 'status' => 'pending', 'paid' => false,
            'confirmation' => ['confirmation_url' => 'https://example.test/demo-payment/' . $id],
        ]);
    } else {
        $id = basename($r->getUri()->getPath());
    }
    if (!isset($provider[$id])) throw new RuntimeException('Unknown demo payment');
    return Create::promiseFor(new Response(200, ['Content-Type' => 'application/json'], json_encode($provider[$id], JSON_THROW_ON_ERROR)));
}]), ['shopId' => 'local-demo', 'secret' => 'local-demo', 'returnBase' => 'http://localhost:8088', 'receipts' => false]));
$c->set(MailerInterface::class, new class implements MailerInterface {
    public function send(RawMessage $message, ?Envelope $envelope = null): void {}
});
$app = (require dirname(__DIR__) . '/config/app.php')($c);
$call = static function (string $method, string $path, array $body = [], int $status = 200, array $headers = []) use ($app, $c): array {
    $c->get(EntityManagerInterface::class)->clear();
    $r = new ServerRequestFactory()->createServerRequest($method, 'http://localhost:8088' . $path)->withParsedBody($body)->withHeader('Accept', 'application/json');
    foreach ($headers as $key => $value) $r = $r->withHeader($key, $value);
    $response = $app->handle($r);
    if ($response->getStatusCode() !== $status) throw new RuntimeException($path . ': HTTP ' . $response->getStatusCode());
    return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR)['data'] ?? [];
};
$orderId = $db->fetchOne('SELECT id FROM orders WHERE JSON_UNQUOTE(JSON_EXTRACT(shipping_address, "$.email"))=? AND payment_status="completed" ORDER BY created_at DESC LIMIT 1', [$guestEmail]);
if (!$orderId) {
    $orderId = $db->transactional(static function () use ($db, $call, $partner, $guestEmail, &$provider): string {
        $offerId = $db->fetchOne('SELECT id FROM partner_offers WHERE user_id=? AND is_active=1 AND (expires_at IS NULL OR expires_at>UTC_TIMESTAMP()) ORDER BY created_at DESC LIMIT 1', [$partner]);
        if (!$offerId) throw new RuntimeException('Create an active basket in the DEMO partner account first.');
        $offer = $call('GET', '/v1/offers/' . $offerId);
        $usersBefore = (int)$db->fetchOne('SELECT COUNT(*) FROM users');
        $order = $call('POST', '/v1/orders/create', [
            'items' => array_map(static fn (array $item): array => ['productId' => $item['product']['id'], 'quantity' => $item['quantity']], $offer['items']),
            'offerId' => $offerId, 'paymentMethod' => 'card', 'deliveryMethod' => 'post',
            'shippingAddress' => ['name' => 'ДЕМО: гость без регистрации', 'email' => $guestEmail, 'phone' => '+79990000000', 'city' => 'Томск', 'address' => 'Тестовый адрес, без отправки', 'postalCode' => '634000', 'comment' => 'ДЕМОНСТРАЦИОННЫЙ ЗАКАЗ. Оплата имитирована локально, реальных денег и доставки нет. Результат сохранён для проверки кабинета партнёра.'],
        ], 201, ['Idempotency-Key' => 'biofarm-visible-guest-demo-checkout-v1']);
        $id = $order['id'];
        if ($db->fetchOne('SELECT user_id FROM orders WHERE id=?', [$id]) !== null) throw new RuntimeException('Expected a guest order');
        if ((int)$db->fetchOne('SELECT COUNT(*) FROM program_ledger WHERE order_id=?', [$id]) !== 0) throw new RuntimeException('Reward credited before payment');
        $call('POST', '/v1/payments/' . $id, ['token' => $order['paymentAccessToken']]);
        $providerId = $db->fetchOne('SELECT provider_id FROM payment_operations WHERE order_id=? AND kind="payment"', [$id]);
        $provider[$providerId]['status'] = 'succeeded';
        $provider[$providerId]['paid'] = true;
        $notification = ['event' => 'payment.succeeded', 'object' => ['id' => $providerId]];
        $call('POST', '/webhooks/yookassa', $notification);
        $reward = (int)$db->fetchOne('SELECT SUM(amount_minor) FROM program_ledger WHERE order_id=? AND user_id=? AND wallet="commission"', [$id, $partner]);
        if ($reward <= 0) throw new RuntimeException('Partner commission is missing');
        $call('POST', '/webhooks/yookassa', $notification);
        if ($reward !== (int)$db->fetchOne('SELECT SUM(amount_minor) FROM program_ledger WHERE order_id=? AND user_id=? AND wallet="commission"', [$id, $partner])) throw new RuntimeException('Duplicate commission');
        if ($usersBefore !== (int)$db->fetchOne('SELECT COUNT(*) FROM users')) throw new RuntimeException('Guest unexpectedly registered');
        return $id;
    });
}
$program = $c->get(ProgramService::class);
$report = [
    'persisted' => true, 'paymentSimulated' => true, 'partnerEmail' => 'demo-partner@biofarm.example.test',
    'order' => $db->fetchAssociative('SELECT id,user_id,subtotal,delivery_cost,total,payment_status,status FROM orders WHERE id=?', [$orderId]),
    'commissions' => $db->fetchAllAssociative('SELECT kind,amount_minor,state FROM program_ledger WHERE order_id=? AND user_id=?', [$orderId, $partner]),
    'balances' => $program->dashboard($partner)['balances']['commission'],
];
echo json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
