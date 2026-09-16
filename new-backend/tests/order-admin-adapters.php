<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\Setting\SiteSettings;
use App\Http\Action\Admin\Order\UpdateOrderDetailsAction;
use App\Http\Action\Admin\Order\UpdateOrderPaymentStatusAction;
use App\Http\Action\Admin\Order\UpdateOrderStatusAction;
use App\Modules\Order\Entity\Order\Order;
use App\Modules\Order\Entity\Order\Persistence\Doctrine\DoctrineOrderRepository;
use App\Modules\Order\Entity\OrderItem\OrderItem;
use App\Modules\Order\Service\OrderBonusApplier;
use App\Modules\Order\Service\OrderEmailNotifier;
use App\Modules\Order\Service\OrderStatusGuard;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\Payment\Service\YooKassaGateway;
use App\Modules\Program\Service\ProgramService;
use App\Modules\User\Entity\UserProfile\UserProfile;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Log\NullLogger;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

function expect(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
$db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
$paths = array_map(static fn (string $path): string => dirname(__DIR__) . '/src/Modules/' . $path, ['Order/Entity', 'Program/Entity', 'User/Entity/UserProfile', 'Payment/Entity']);
$config = ORMSetup::createAttributeMetadataConfiguration($paths, true);
$config->enableNativeLazyObjects(true);
$config->setNamingStrategy(new UnderscoreNamingStrategy());
$em = new EntityManager($db, $config);
new SchemaTool($em)->createSchema($em->getMetadataFactory()->getAllMetadata());
$db->executeStatement('CREATE TABLE promo_code_redemptions (order_id TEXT,promo_code_id INTEGER)');
$db->executeStatement('CREATE TABLE promo_codes (id INTEGER,used_count INTEGER)');
$db->executeStatement('CREATE TABLE site_settings (`key` TEXT,value TEXT)');
$db->insert('site_settings', ['key' => 'cart_enabled', 'value' => 'true']);
$db->insert('site_settings', ['key' => 'referral_enabled', 'value' => 'true']);
$program = new ProgramService($db);
$guard = new OrderStatusGuard($db);
$repo = new DoctrineOrderRepository($em);
$flusher = new class($em) implements FlusherInterface {
    public function __construct(private EntityManager $em) {}

    public function flush(): void
    {
        $this->em->flush();
    }
};
$cacher = new class implements Cacher {
    public function get(string $key): array|bool|float|int|object|string|null
    {
        return null;
    }

    public function set(string $key, array|bool|float|int|object|string|null $value, ?int $ttl = null): bool
    {
        return true;
    }

    public function setTagged(string $key, array|bool|float|int|object|string|null $value, int $ttl, array $tags): bool
    {
        return true;
    }

    public function delete(string $key): void {}

    public function deleteTag(string $tag): void {}

    public function expire(string $key, int $ttl): void {}

    public function mGet(array $keys): array
    {
        return [];
    }

    public function zAdd(string $key, float $score, float|int|string $value): void {}

    public function zRangeByScore(string $key, int $min, int $max, ?int $offset = null, ?int $count = null): array
    {
        return [];
    }

    public function zRevRangeByScore(string $key, int $max, int $min, ?int $offset = null, ?int $count = null): array
    {
        return [];
    }

    public function increase(string $key, int $value): void {}

    public function decrease(string $key, int $value): void {}

    public function sAdd(string $key, string $value): void {}

    public function sMembers(string $key): array
    {
        return [];
    }
};
$mailer = new class implements MailerInterface {
    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        throw new RuntimeException('No test may send email');
    }
};
$notifier = new OrderEmailNotifier(new SiteSettings($db), $mailer, new Environment(new ArrayLoader()), $db, new NullLogger());
$applier = new OrderBonusApplier($program, $flusher, new PaymentService($db, new YooKassaGateway(null, ['shopId' => '', 'secret' => '']), $program));
$details = new UpdateOrderDetailsAction($program, $repo, $applier, $notifier, $guard, $cacher, $flusher);
$payment = new UpdateOrderPaymentStatusAction($program, $repo, $applier, $notifier, $guard, $cacher, $flusher);
$status = new UpdateOrderStatusAction($applier, $program, $repo, $notifier, $guard, $cacher, $flusher);
$app = AppFactory::create();
foreach (['details' => $details, 'payment' => $payment, 'status' => $status] as $route => $action) {
    $app->patch('/' . $route . '/{id}', static fn ($request) => $action->handle($request));
}$app->addRoutingMiddleware();
$send = static function (string $route, string $id, array $payload) use ($app, $em) {
    $em->clear();
    return $app->handle(new ServerRequestFactory()->createServerRequest('PATCH', '/' . $route . '/' . $id)->withParsedBody($payload));
};
$reject = static function (string $route, string $id, array $payload, string $message) use ($send): void {
    try {
        $send($route, $id, $payload);
    } catch (DomainExceptionModule $e) {
        expect($e->getStatus() === 409, $message . ' response code');
        return;
    }throw new RuntimeException($message);
};
$em->persist(UserProfile::create(1, bonusBalance: 1000, referralCode: 'fixture'));
$em->flush();
$make = static function (string $id) use ($em, $program): void {
    $order = Order::create($id, 1, 1000, ['email' => 'fixture@example.test', 'city' => 'Before'], 'card', subtotal: 1000);
    $em->persist($order);
    $em->persist(OrderItem::create($id, 1, 'Fixture product', 1000, 1));
    $em->flush();
    $program->captureOrder($id);
};
$make('A');
$full = ['user_id' => 1, 'subtotal' => 1000, 'total' => 1000, 'delivery_cost' => 0, 'discount_amount' => 0, 'bonus_used' => 0, 'bonus_earned' => 0, 'promo_code' => null, 'referred_by' => null, 'payment_method' => 'card', 'status' => 'pending', 'payment_status' => 'pending', 'items' => [['product_id' => 1, 'price' => 1000, 'quantity' => 1]], 'tracking_number' => 'fixture-track', 'shipping_address' => ['city' => 'After']];
expect($send('details', 'A', $full)->getStatusCode() === 200, 'full unchanged financial form allowed');
expect($db->fetchOne("SELECT tracking_number FROM orders WHERE id='A'") === 'fixture-track', 'tracking saved');
expect(json_decode($db->fetchOne("SELECT shipping_address FROM orders WHERE id='A'"), true)['city'] === 'After', 'shipping saved');
foreach (['subtotal' => 900, 'total' => 900, 'bonus_used' => 10, 'user_id' => 2, 'referred_by' => 'other', 'promo_code' => 'other'] as $field => $value) {
    $reject('details', 'A', [$field => $value], 'financial field immutable ' . $field);
}
$reject('details', 'A', ['items' => [['product_id' => 1, 'quantity' => 2, 'price' => 1000]]], 'items immutable');
foreach (['creating', 'pending', 'waiting_for_capture', 'retry_required', 'review_required'] as $providerStatus) {
    $db->insert('payment_operations', ['id' => 'op-' . $providerStatus, 'order_id' => 'A', 'kind' => 'payment', 'status' => $providerStatus, 'amount_minor' => 100000, 'provider_id' => null, 'request_payload' => '{}', 'confirmation_url' => null, 'created_at' => '2026-09-17 00:00:00', 'updated_at' => '2026-09-17 00:00:00']);
    $reject('details', 'A', ['payment_status' => 'completed'], 'active payment details');
    $reject('payment', 'A', ['payment_status' => 'completed'], 'active payment endpoint');
    $reject('status', 'A', ['status' => 'cancelled'], 'active payment cancellation');
    $db->delete('payment_operations', ['id' => 'op-' . $providerStatus]);
}
$send('details', 'A', ['payment_status' => 'completed']);
$send('payment', 'A', ['payment_status' => 'completed']);
expect((int)$db->fetchOne("SELECT COUNT(*) FROM program_ledger WHERE order_id='A' AND kind='buyer'") === 1, 'payment settles once');
$reject('details', 'A', ['payment_status' => 'pending'], 'paid reset details');
$reject('payment', 'A', ['payment_status' => 'failed'], 'paid reset payment');
$reject('details', 'A', ['payment_status' => 'refunded'], 'unconfirmed refund');
$send('details', 'A', ['status' => 'delivered']);
expect($db->fetchOne("SELECT delivered_at FROM program_orders WHERE id='A'") !== null, 'details delivered starts hold');
$db->update('orders', ['payment_status' => 'refunded'], ['id' => 'A']);
$send('details', 'A', ['tracking_number' => 'after-refund']);
$reject('payment', 'A', ['payment_status' => 'completed'], 'refunded reset');
$reject('details', 'A', ['payment_status' => 'pending'], 'refunded reset details');
$make('B');
$send('details', 'B', ['payment_status' => 'failed']);
expect($db->fetchOne("SELECT status FROM program_orders WHERE id='B'") === 'pending', 'failed payment leaves snapshot retryable');
$send('payment', 'B', ['payment_status' => 'pending']);
$send('details', 'B', ['status' => 'cancelled']);
expect($db->fetchOne("SELECT status FROM program_orders WHERE id='B'") === 'cancelled', 'details cancel reaches domain');
$reject('details', 'B', ['status' => 'pending'], 'cancelled details reopen');
$reject('status', 'B', ['status' => 'processing'], 'cancelled endpoint reopen');
$reject('payment', 'B', ['payment_status' => 'completed'], 'cancelled settlement');
echo "order-admin-adapters: all assertions passed\n";
