<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Components\Exception\DomainExceptionModule;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\Payment\Service\YooKassaGateway;
use App\Modules\Program\Service\ProgramService;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;

function verify(bool $yes, string $message): void
{
    if (!$yes) {
        throw new RuntimeException($message);
    }
}
$db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
$config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__) . '/src/Modules/Program/Entity', dirname(__DIR__) . '/src/Modules/Payment/Entity'], true);
$config->enableNativeLazyObjects(true);
$config->setNamingStrategy(new UnderscoreNamingStrategy());
$em = new EntityManager($db, $config);
new SchemaTool($em)->createSchema($em->getMetadataFactory()->getAllMetadata());
$db->executeStatement('CREATE TABLE orders(id TEXT PRIMARY KEY,status TEXT,payment_status TEXT,total INTEGER,delivery_cost INTEGER,shipping_address TEXT,updated_at TEXT)');
$db->executeStatement('CREATE TABLE site_settings(`key` TEXT,value TEXT)');
$program = new ProgramService($db);
$requests = [];
$receiptObjects = [];
$transportFail = false;
$mismatch = false;
$providerReceiptStatus = 'succeeded';
$handler = static function ($request, array $options) use ($db, &$requests, &$receiptObjects, &$transportFail, &$mismatch, &$providerReceiptStatus) {
    verify(!$db->isTransactionActive(), 'HTTP call must be outside database transaction');
    $requests[] = $request;
    if (str_contains($request->getUri()->getPath(), 'stalled')) {
        return Create::promiseFor(new Response(503, [], '{}'));
    }
    if ($transportFail) {
        return Create::promiseFor(new Response(503, [], '{}'));
    }
    $payload = json_decode((string)$request->getBody(), true);
    $path = $request->getUri()->getPath();
    if ($request->getMethod() === 'POST' && $path === '/v3/receipts') {
        $key = $request->getHeaderLine('Idempotence-Key');
        $receiptObjects[$key] = $payload + ['id' => 'receipt-' . $key, 'status' => 'pending'];
        return Create::promiseFor(new Response(200, [], json_encode($receiptObjects[$key])));
    }
    if ($request->getMethod() === 'GET' && str_starts_with($path, '/v3/receipts/')) {
        $key = substr(basename($path), strlen('receipt-'));
        $object = $receiptObjects[$key];
        $object['status'] = $providerReceiptStatus;
        if ($mismatch) {
            $object['payment_id'] = 'wrong-payment';
        }return Create::promiseFor(new Response(200, [], json_encode($object)));
    }
    if ($request->getMethod() === 'POST' && $path === '/v3/refunds') {
        return Create::promiseFor(new Response(200, [], json_encode(['id' => 'refund-' . $request->getHeaderLine('Idempotence-Key'), 'status' => 'succeeded', 'payment_id' => $payload['payment_id'], 'amount' => $payload['amount']])));
    }
    throw new RuntimeException('Unexpected fake request ' . $path);
};
$gateway = new YooKassaGateway(new Client(['handler' => $handler]), ['shopId' => 'fixture', 'secret' => 'fixture', 'returnBase' => 'https://example.test', 'receipts' => true, 'vatCode' => 1, 'taxSystem' => 2]);
$payments = new PaymentService($db, $gateway, $program);
$fixture = static function (string $id, int $itemId = 1) use ($db, $gateway): void {
    $order = ['id' => $id, 'status' => 'processing', 'payment_status' => 'completed', 'total' => 3, 'delivery_cost' => 1, 'shipping_address' => json_encode(['email' => 'fixture@example.test']), 'updated_at' => gmdate('Y-m-d H:i:s')];
    $db->insert('orders', $order);
    $snapshot = ['items' => [['itemId' => $itemId, 'description' => 'Fixture product', 'productId' => 1, 'quantity' => 3, 'paidMinor' => 200, 'spentMinor' => 0, 'discountMinor' => 100, 'refundedQuantity' => 0, 'rewards' => []]], 'rules' => ['holdDays' => 14], 'deliveryMinor' => 100, 'deliveryRefunded' => false, 'bonusMinor' => 0, 'totalMinor' => 300, 'recipients' => []];
    $db->insert('program_orders', ['id' => $id, 'buyer_id' => null, 'status' => 'paid', 'snapshot' => json_encode($snapshot), 'delivered_at' => null]);
    $initial = $gateway->receipt($order, [['description' => 'Fixture product', 'quantity' => 2, 'amountMinor' => 67], ['description' => 'Fixture product', 'quantity' => 1, 'amountMinor' => 66], ['description' => 'Доставка', 'quantity' => 1, 'amountMinor' => 100, 'delivery' => true]]);
    $db->insert('payment_operations', ['id' => 'pay-' . $id, 'order_id' => $id, 'kind' => 'payment', 'provider_id' => 'provider-' . $id, 'amount_minor' => 300, 'status' => 'succeeded', 'request_payload' => json_encode(['receipt' => $initial]), 'confirmation_url' => null, 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')]);
};
$fixture('A');
$pre = $payments->requestRefund('A', 'pre-refund-key-0001', [['itemId' => 1, 'quantity' => 1]], false);
verify($pre['status'] === 'succeeded', 'pre-delivery refund confirmed');
$preBody = json_decode((string)$requests[0]->getBody(), true);
verify($preBody['receipt']['items'][0]['payment_mode'] === 'full_prepayment', 'before-delivery refund mode');
$program->atomic(static function () use ($program, $payments): void {
    $program->deliverOrder('A');
    $payments->queueSettlementReceipt('A');
});
$before = count($requests);
$payments->queueSettlementReceipt('A');
verify(count($requests) === $before, 'queue makes no network');
verify((int)$db->fetchOne("SELECT COUNT(*) FROM payment_operations WHERE kind='receipt' AND order_id='A'") === 1, 'outbox unique');
$result = $payments->settlementReceipt('A');
verify($result['status'] === 'succeeded', 'settlement verified');
$receipt = json_decode((string)$requests[$before]->getBody(), true);
verify($receipt['type'] === 'payment' && $receipt['send'] === true && $receipt['payment_id'] === 'provider-A', 'receipt envelope');
verify($receipt['tax_system_code'] === 2, 'original taxation retained');
verify($receipt['settlements'][0]['type'] === 'prepayment' && $receipt['settlements'][0]['amount']['value'] === '2.34', 'settles unrefunded 134+100 cash');
verify($receipt['items'][0]['quantity'] === '2' && $receipt['items'][0]['amount']['value'] === '0.67', 'remaining quantities cumulative rounding');
verify(array_unique(array_column($receipt['items'], 'payment_mode')) === ['full_payment'], 'all items full payment');
verify($requests[$before + 1]->getMethod() === 'GET', 'GET verification after POST');
$count = count($requests);
$payments->settlementReceipt('A');
verify(count($requests) === $count, 'completed receipt never resent');
$payments->requestRefund('A', 'post-refund-key-0001', [['itemId' => 1, 'quantity' => 1]], false);
$post = json_decode((string)end($requests)->getBody(), true);
verify($post['receipt']['items'][0]['payment_mode'] === 'full_payment', 'after delivery refund mode');
$fixture('B', 2);
$program->reserveRefund('B', 'pending-pre', [['itemId' => 2, 'quantity' => 1]], false);
$program->deliverOrder('B');
$before = count($requests);
verify($payments->settlementReceipt('B')['status'] === 'waiting_refund', 'pending refund defers receipt');
verify(count($requests) === $before, 'no network while refund pending');
$program->completeRefund('pending-pre');
$results = $payments->reconcile();
verify($db->fetchOne("SELECT status FROM payment_operations WHERE order_id='B' AND kind='receipt'") === 'succeeded', 'reconcile unblocks after refund');
$fixture('C', 3);
$program->deliverOrder('C');
$transportFail = true;
verify($payments->settlementReceipt('C')['status'] === 'retry_required', 'receipt failure visible');
verify($db->fetchOne("SELECT status FROM orders WHERE id='C'") === 'delivered', 'failure preserves delivery');
$key = end($requests)->getHeaderLine('Idempotence-Key');
$transportFail = false;
verify($payments->settlementReceipt('C')['status'] === 'succeeded', 'retry completes');
$posts = array_values(array_filter($requests, static fn ($r) => $r->getHeaderLine('Idempotence-Key') === $key));
verify(count($posts) === 2, 'same-key transport retry');
$fixture('D', 4);
$program->deliverOrder('D');
$mismatch = true;
verify($payments->settlementReceipt('D')['status'] === 'retry_required', 'foreign receipt rejected');
$mismatch = false;
$before = count($requests);
verify($payments->settlementReceipt('D')['status'] === 'succeeded', 'authoritative GET retry');
verify(count($requests) === $before + 1 && end($requests)->getMethod() === 'GET', 'known receipt only GET retry');
$fixture('E', 5);
$program->reserveRefund('E', 'full-refund', [['itemId' => 5, 'quantity' => 3]], true);
$program->completeRefund('full-refund');
$program->deliverOrder('E');
$before = count($requests);
verify($payments->settlementReceipt('E')['status'] === 'skipped', 'zero remaining receipt skipped');
verify(count($requests) === $before, 'no zero receipt provider call');
$fixture('F', 6);
$program->deliverOrder('F');
$transportFail = true;
$payments->settlementReceipt('F');
$db->executeStatement("UPDATE payment_operations SET created_at='2020-01-01 00:00:00' WHERE order_id='F' AND kind='receipt'");
$transportFail = false;
$before = count($requests);
verify($payments->settlementReceipt('F')['status'] === 'review_required', 'expired ambiguous key needs review');
verify(count($requests) === $before, 'no unsafe repeated POST after window');
// More stalled rows than the batch limit cannot monopolize receipt processing.
$fixture('G', 7);
$program->deliverOrder('G');
foreach (['payment', 'refund'] as $kind) {
    for ($i = 0; $i < 8; ++$i) {
        $db->insert('payment_operations', ['id' => 'stalled-' . $kind . '-' . $i, 'order_id' => 'G', 'kind' => $kind, 'provider_id' => 'stalled-' . $kind . '-' . $i, 'status' => 'pending', 'amount_minor' => 1, 'request_payload' => '{}', 'confirmation_url' => null, 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => '2000-01-01 00:00:00']);
    }
    $db->insert('payment_operations', ['id' => 'expired-' . $kind, 'order_id' => 'G', 'kind' => $kind, 'provider_id' => null, 'status' => 'creating', 'amount_minor' => 1, 'request_payload' => '{}', 'confirmation_url' => null, 'created_at' => '2000-01-01 00:00:00', 'updated_at' => '2000-01-01 00:00:00']);
}
$batch = $payments->reconcile(3);
verify(count($batch) === 3, 'bounded fair batch');
verify($db->fetchOne("SELECT status FROM payment_operations WHERE order_id='G' AND kind='receipt'") === 'succeeded', 'receipt processed despite16 stalled payment/refund rows');
verify((int)$db->fetchOne("SELECT COUNT(*) FROM payment_operations WHERE id LIKE 'expired-%' AND status='review_required'") === 2, 'unknown expired payments/refunds excluded for review');
$attempted = array_column($batch, 'id');
$next = $payments->reconcile(3);
verify(!array_intersect($attempted, array_column($next, 'id')), 'failed rows rotate behind other queued rows');
$fixture('H', 8);
$db->update('program_orders', ['status' => 'pending'], ['id' => 'H']);
$db->update('orders', ['payment_status' => 'pending'], ['id' => 'H']);
$db->update('payment_operations', ['status' => 'creating', 'provider_id' => null, 'created_at' => '2000-01-01 00:00:00'], ['id' => 'pay-H']);
$payments->reconcile(3);
verify($db->fetchOne("SELECT status FROM payment_operations WHERE id='pay-H'") === 'review_required', 'expired unknown charge enters manual review');
try {
    $program->cancelOrder('H');
    throw new RuntimeException('Unknown expired charge allowed order cancellation');
} catch (DomainException) {
}
verify($db->fetchOne("SELECT status FROM program_orders WHERE id='H'") === 'pending', 'manual review preserves order and reservations');
// Recover a known late receipt by authoritative GET and audit; never a new POST.
$recoveryPayload = json_decode($db->fetchOne("SELECT request_payload FROM payment_operations WHERE order_id='F' AND kind='receipt'"), true);
$receiptObjects['recovery-good'] = $recoveryPayload + ['id' => 'receipt-recovery-good', 'status' => 'succeeded'];
$receiptObjects['recovery-wrong'] = $recoveryPayload + ['id' => 'receipt-recovery-wrong', 'status' => 'succeeded'];
$receiptObjects['recovery-wrong']['payment_id'] = 'unrelated-payment';
$receiptObjects['recovery-items'] = $recoveryPayload + ['id' => 'receipt-recovery-items', 'status' => 'succeeded'];
$receiptObjects['recovery-items']['items'][0]['amount']['value'] = '9.99';
foreach (['receipt-recovery-wrong', 'receipt-recovery-items'] as $bad) {
    try {
        $payments->recoverSettlementReceipt('F', $bad, 'Fixture review', 42);
        throw new RuntimeException('Invalid recovery accepted');
    } catch (DomainExceptionModule) {
    }
}
$before = count($requests);
$recovered = $payments->recoverSettlementReceipt('F', 'receipt-recovery-good', 'Fixture manual reconciliation', 42);
verify($recovered['status'] === 'succeeded', 'manual verified receipt attached');
verify(count($requests) === $before + 1 && end($requests)->getMethod() === 'GET', 'recovery only GET no fiscal POST');
verify((int)$db->fetchOne("SELECT COUNT(*) FROM program_audit WHERE kind='receipt_recovery' AND actor_id=42") === 1, 'recovery audited actor reason');
$payments->requestRefund('F', 'recovered-refund-0001', [['itemId' => 6, 'quantity' => 1]], false);
verify(end($requests)->getUri()->getPath() === '/v3/refunds', 'recovery unblocks postdelivery refund');
$db->executeStatement("UPDATE payment_operations SET status='canceled' WHERE order_id='D' AND kind='receipt'");
$receiptObjects['canceled-recovery'] = json_decode($db->fetchOne("SELECT request_payload FROM payment_operations WHERE order_id='D' AND kind='receipt'"), true) + ['id' => 'receipt-canceled-recovery', 'status' => 'succeeded'];
$before = count($requests);
verify($payments->recoverSettlementReceipt('D', 'receipt-canceled-recovery', 'Fixture canceled status reconciliation', 42)['status'] === 'succeeded', 'canceled receipt can be reconciled to existing verified document');
verify(count($requests) === $before + 1 && end($requests)->getMethod() === 'GET', 'canceled recovery never posts a duplicate');
echo "payment-receipts: all assertions passed (fake HTTP only)\n";
