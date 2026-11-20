<?php

declare(strict_types=1);

namespace App\Modules\Payment\Service;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Setting\SiteSettings;
use App\Modules\Program\Service\ProgramMath;
use App\Modules\Program\Service\ProgramService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Throwable;

final readonly class PaymentService
{
    public function __construct(private Connection $db, private YooKassaGateway $gateway, private ProgramService $program) {}

    public function issueAccess(string $orderId, ?string $token = null): string
    {
        $token ??= bin2hex(random_bytes(32));
        $this->db->insert('payment_sessions', ['order_id' => $orderId, 'token_hash' => hash('sha256', $token), 'created_at' => gmdate('Y-m-d H:i:s')]);
        return $token;
    }

    public function authorize(string $orderId, ?int $userId, string $token, bool $admin = false): array
    {
        $order = $this->db->fetchAssociative('SELECT * FROM orders WHERE id=?', [$orderId]);
        if (!$order) {
            throw new DomainExceptionModule('payment', 'Заказ не найден.', 10, status: 404);
        }
        $owner = $userId !== null && $order['user_id'] !== null && (int)$order['user_id'] === $userId;
        $hash = $this->db->fetchOne('SELECT token_hash FROM payment_sessions WHERE order_id=?', [$orderId]);
        if (!$admin && !$owner && !(\strlen($token) === 64 && \is_string($hash) && hash_equals($hash, hash('sha256', $token)))) {
            throw new DomainExceptionModule('payment', 'Нет доступа к оплате этого заказа.', 11, status: 403);
        }
        return $order;
    }

    public function start(string $orderId): array
    {
        $completed = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE order_id=? AND kind='payment' AND status='succeeded' LIMIT 1", [$orderId]);
        if ($completed && $this->isInternalPayment($completed)) {
            return $this->status($orderId, false);
        }
        if ($this->completeCheckoutWithoutCash($orderId)) {
            return $this->status($orderId, false);
        }
        if (!$this->gateway->configured()) {
            throw new DomainExceptionModule('payment', 'Онлайн-оплата не настроена. Заказ сохранён, свяжитесь с магазином.', 12, status: 503);
        }
        $operation = $this->program->atomic(function () use ($orderId): array {
            $order = $this->db->fetchAssociative($this->lockSql('SELECT * FROM orders WHERE id=?'), [$orderId]);
            if (!$order || \in_array($order['status'], ['cancelled', 'canceled'], true) || \in_array($order['payment_status'], ['completed', 'refunded'], true)) {
                throw new DomainExceptionModule('payment', 'Заказ уже оплачен или отменён.', 13, status: 409);
            }
            if (($order['delivery_method'] ?? null) === 'ozon-pickup' && !$this->gateway->publicConfig()['receiptsEnabled']) {
                throw new DomainExceptionModule('payment', 'Оплата заказов Ozon временно недоступна.', 12, status: 503);
            }
            $current = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE order_id=? AND kind='payment' AND status<>'canceled' ORDER BY created_at DESC LIMIT 1", [$orderId]);
            if ($current) {
                return $current;
            }
            $amount = ProgramMath::minor($order['total']);
            if ($amount <= 0) {
                throw new DomainExceptionModule('payment', 'Для онлайн-оплаты нужна положительная сумма.', 14, status: 422);
            }
            $id = bin2hex(random_bytes(16));
            $payload = ['amount' => ['value' => YooKassaGateway::money($amount), 'currency' => 'RUB'], 'capture' => true, 'confirmation' => ['type' => 'redirect', 'return_url' => $this->gateway->returnUrl($orderId)], 'description' => mb_substr('Заказ ' . $orderId, 0, 128), 'metadata' => ['order_id' => $orderId, 'operation_id' => $id]];
            $receipt = $this->gateway->receipt($order, $this->receiptItems($order));
            if ($receipt !== null) {
                $payload['receipt'] = $receipt;
            }
            $row = ['id' => $id, 'order_id' => $orderId, 'kind' => 'payment', 'provider_id' => null, 'amount_minor' => $amount, 'status' => 'creating', 'request_payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'confirmation_url' => null, 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')];
            $this->db->insert('payment_operations', $row);
            return $row;
        });
        if ($operation['provider_id']) {
            return $this->refresh($operation);
        }
        $this->assertRetryWindow($operation);
        $result = $this->gateway->createPayment(json_decode($operation['request_payload'], true, 512, JSON_THROW_ON_ERROR), $operation['id']);
        $this->acceptPayment($operation, $result);
        return $this->status($orderId, false);
    }

    /** Called only while creating a checkout; the client cannot enable this mode. */
    public function completeCheckoutForTesting(string $orderId): bool
    {
        if (!new SiteSettings($this->db)->simulatesCheckoutPayments()) {
            return false;
        }
        return $this->completeInternalCheckout($orderId, ['testing' => true]);
    }

    /** A zero monetary total is settled internally; it is not a simulated provider payment. */
    public function completeCheckoutWithoutCash(string $orderId): bool
    {
        return $this->program->atomic(function () use ($orderId): bool {
            $total = $this->db->fetchOne($this->lockSql('SELECT total FROM orders WHERE id=?'), [$orderId]);
            if ($total === false || ProgramMath::minor($total) !== 0) return false;
            return $this->completeInternalCheckout($orderId, ['noCashPayment' => true]);
        });
    }

    private function completeInternalCheckout(string $orderId, array $reason): bool
    {
        return $this->program->atomic(function () use ($orderId, $reason): bool {
            $order = $this->db->fetchAssociative($this->lockSql('SELECT * FROM orders WHERE id=?'), [$orderId]);
            if (!$order || \in_array($order['status'], ['cancelled', 'canceled'], true) || $order['payment_status'] === 'refunded') {
                throw new DomainExceptionModule('payment', 'Заказ уже оплачен или отменён.', 13, status: 409);
            }
            $current = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE order_id=? AND kind='payment' AND status<>'canceled' LIMIT 1", [$orderId]);
            if ($current && $this->isInternalPayment($current) && $current['status'] === 'succeeded' && $order['payment_status'] === 'completed') {
                return true;
            }
            if ($current || $order['payment_status'] === 'completed') {
                throw new DomainExceptionModule('payment', 'По заказу уже начата оплата.', 37, status: 409);
            }
            $now = gmdate('Y-m-d H:i:s');
            $this->db->insert('payment_operations', [
                'id' => bin2hex(random_bytes(16)), 'order_id' => $orderId, 'kind' => 'payment',
                'provider_id' => null, 'amount_minor' => ProgramMath::minor($order['total']), 'status' => 'succeeded',
                // Internal routing only: never send this operation to the real provider, even after disabling testing.
                'request_payload' => json_encode($reason, JSON_THROW_ON_ERROR),
                'confirmation_url' => null, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->db->update('orders', ['payment_status' => 'completed', 'paid_at' => $now, 'updated_at' => $now], ['id' => $orderId]);
            $this->program->settleOrder($orderId);
            return true;
        });
    }

    private function isInternalPayment(array $operation): bool
    {
        $payload = json_decode($operation['request_payload'], true, 512, JSON_THROW_ON_ERROR);
        return ($payload['testing'] ?? false) === true || ($payload['noCashPayment'] ?? false) === true;
    }

    public function status(string $orderId, bool $refresh = true): array
    {
        $operation = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE order_id=? AND kind='payment' ORDER BY CASE WHEN status='canceled' THEN 1 ELSE 0 END,created_at DESC,id DESC LIMIT 1", [$orderId]);
        if ($refresh && $operation && $operation['provider_id'] && !\in_array($operation['status'], ['succeeded', 'canceled'], true)) {
            return $this->refresh($operation);
        }
        $order = $this->db->fetchAssociative('SELECT payment_status,status,total FROM orders WHERE id=?', [$orderId]);
        return ['orderId' => $orderId, 'orderStatus' => $order['status'] ?? 'pending', 'configured' => ($operation && $this->isInternalPayment($operation)) || $this->gateway->configured(), 'status' => $operation['status'] ?? 'not_started', 'orderPaymentStatus' => $order['payment_status'] ?? 'pending', 'amountMinor' => ProgramMath::minor($order['total'] ?? 0), 'confirmationUrl' => $operation['confirmation_url'] ?? null];
    }

    public function webhook(array $notification): void
    {
        $event = $notification['event'] ?? '';
        $id = $notification['object']['id'] ?? null;
        if (!\is_string($id) || \strlen($id) > 64 || !\in_array($event, ['payment.succeeded', 'payment.canceled', 'payment.waiting_for_capture', 'refund.succeeded'], true)) {
            return;
        }
        // Never trust status/amount/metadata delivered by the caller. Fetch the object over authenticated API.
        if (str_starts_with($event, 'payment.')) {
            $verified = $this->gateway->payment($id);
            $operationId = $verified['metadata']['operation_id'] ?? '';
            $operation = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE kind='payment' AND (provider_id=? OR id=?) LIMIT 1", [$id, $operationId]);
            if ($operation) {
                $this->acceptPayment($operation, $verified);
            }
        } else {
            $verified = $this->gateway->refund($id);
            $operation = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE kind='refund' AND provider_id=?", [$id]);
            if ($operation) {
                $this->acceptRefund($operation, $verified);
            }
        }
    }

    public function requestRefund(string $orderId, string $requestId, array $items, bool $delivery, bool $confirmedOzon = false): array
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{16,64}$/D', $requestId)) {
            throw new DomainExceptionModule('payment', 'Нужен уникальный ключ возврата.', 20, status: 422);
        }
        $id = hash('sha256', $orderId . ':' . $requestId);
        // Fiscal delivery must be resolved before starting a new post-delivery refund.
        if ($this->db->fetchOne('SELECT id FROM payment_operations WHERE id=?', [$id]) === false
            && $this->isDelivered($orderId)) {
            $receipt = $this->settlementReceipt($orderId);
            if (!\in_array($receipt['status'], ['succeeded', 'skipped', 'not_required'], true)) {
                throw new DomainExceptionModule('payment', 'Сначала завершите чек зачёта предоплаты; возврат ещё не создан.', 28, status: 409);
            }
        }
        $operation = $this->program->atomic(function () use ($orderId, $id, $items, $delivery, $confirmedOzon): array {
            $order = $this->db->fetchAssociative($this->lockSql('SELECT * FROM orders WHERE id=?'), [$orderId]);
            $liveOzon = ($order['delivery_method'] ?? null) === 'ozon-pickup';
            $ozonState = $liveOzon ? $this->db->fetchOne("SELECT status FROM ozon_delivery_orders WHERE order_id=? AND mode='live'", [$orderId]) : false;
            if ($liveOzon && $ozonState !== 'delivered'
                && !($confirmedOzon && \in_array($ozonState, ['cancel_confirmed', 'partial_refund'], true))) {
                throw new DomainExceptionModule('payment', 'Дождитесь подтверждения отмены или выдачи заказа Ozon.', 29, status: 409);
            }
            $existing = $this->db->fetchAssociative('SELECT * FROM payment_operations WHERE id=?', [$id]);
            if ($existing) {
                $this->program->reserveRefund($orderId, $id, $items, $delivery);
                return $existing;
            }
            $payment = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE order_id=? AND kind='payment' AND status='succeeded' LIMIT 1", [$orderId]);
            if (!$payment) {
                throw new DomainExceptionModule('payment', 'У заказа нет подтверждённой оплаты через ЮKassa.', 21, status: 422);
            }
            $delivered = $this->isDelivered($orderId);
            if ($delivered) {
                $original = json_decode($payment['request_payload'], true, 512, JSON_THROW_ON_ERROR);
                $statuses = $this->db->fetchFirstColumn("SELECT status FROM payment_operations WHERE order_id=? AND kind='receipt'", [$orderId]);
                if (isset($original['receipt']) && ($statuses === [] || array_diff($statuses, ['succeeded', 'skipped']) !== [])) {
                    throw new DomainExceptionModule('payment', 'Сначала завершите чек зачёта предоплаты; возврат ещё не создан.', 28, status: 409);
                }
            }

            $fiscal = $delivered && $ozonState === 'delivered' ? $this->ozonFiscalItems($orderId) : [];
            $units = $this->selectRefundUnits($orderId, $items, $fiscal);
            $refund = $this->program->reserveRefund($orderId, $id, $items, $delivery, $units);
            $amount = (int)$refund['amountMinor'];
            if ($amount < 0) {
                throw new DomainExceptionModule('payment', 'Некорректная сумма возврата.', 22, status: 422);
            }
            $payload = ['payment_id' => $payment['provider_id'], 'amount' => ['value' => YooKassaGateway::money($amount), 'currency' => 'RUB'], 'description' => 'Возврат по заказу ' . $orderId];
            $testing = $this->isInternalPayment($payment);
            if ($testing) {
                $payload['testing'] = true;
            }
            $marks = $fiscal === [] || $units !== [] ? [] : $this->selectRefundMarks($orderId, $items);
            $receipt = $amount > 0 && !$testing ? $this->gateway->receipt($order, $this->refundReceiptItems($refund['items'], $marks, $units), $delivered ? 'full_payment' : 'full_prepayment') : null;
            if ($receipt !== null) {
                $payload['receipt'] = $receipt;
            }
            $row = ['id' => $id, 'order_id' => $orderId, 'kind' => 'refund', 'provider_id' => null, 'amount_minor' => $amount, 'status' => 'creating', 'request_payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'confirmation_url' => null, 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')];
            $this->db->insert('payment_operations', $row);
            if ($amount === 0 || $testing) {
                $this->program->completeRefund($id);
                $row['status'] = 'succeeded';
                $this->db->update('payment_operations', ['status' => 'succeeded'], ['id' => $id]);
            }
            return $row;
        });
        if (\in_array($operation['status'], ['succeeded', 'canceled'], true)) {
            return ['id' => $id, 'status' => $operation['status']];
        }
        if ($operation['provider_id']) {
            $verified = $this->gateway->refund($operation['provider_id']);
        } else {
            $this->assertRetryWindow($operation);
            $verified = $this->gateway->createRefund(json_decode($operation['request_payload'], true, 512, JSON_THROW_ON_ERROR), $id);
        }
        $this->acceptRefund($operation, $verified);
        return ['id' => $id, 'status' => $verified['status']];
    }

    public function reconcile(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        // Legacy order identifiers may use a different MariaDB collation from new outbox tables.
        $binary = $this->db->getDatabasePlatform() instanceof SQLitePlatform ? '' : 'BINARY ';
        $missing = $this->db->fetchFirstColumn("SELECT DISTINCT o.id FROM orders o JOIN payment_operations p ON {$binary}p.order_id={$binary}o.id AND p.kind='payment' AND p.status='succeeded' WHERE o.status='delivered' AND NOT EXISTS (SELECT 1 FROM payment_operations r WHERE {$binary}r.order_id={$binary}o.id AND r.kind='receipt') LIMIT " . $limit);
        foreach ($missing as $orderId) {
            $this->queueSettlementReceipt($orderId);
        }
        // An unknown POST may already have succeeded. Expired provider keys require manual review.
        $this->db->executeStatement("UPDATE payment_operations SET status='review_required',updated_at=? WHERE kind IN ('payment','refund') AND provider_id IS NULL AND status IN ('creating','retry_required') AND created_at<?", [gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s', time() - 23 * 3600)]);
        // Fair rounds: each kind gets its oldest candidate before another kind gets its next.
        $rows = $this->db->fetchAllAssociative("SELECT * FROM payment_operations WHERE status IN ('creating','pending','waiting_for_capture','queued','waiting_refund','retry_required') ORDER BY ROW_NUMBER() OVER (PARTITION BY kind ORDER BY updated_at,created_at,id),updated_at,created_at,id LIMIT " . $limit);
        $results = [];
        foreach ($rows as $row) {
            try {
                if ($row['kind'] === 'receipt') {
                    $receipt = $this->settlementReceipt($row['order_id'], $row['id']);
                    $results[] = ['id' => $row['id'], 'checked' => \in_array($receipt['status'], ['pending', 'succeeded', 'skipped'], true), 'status' => $receipt['status']];
                    continue;
                }
                if ($row['kind'] === 'payment') {
                    if ($row['provider_id']) {
                        $this->refresh($row);
                    } else {
                        $this->assertRetryWindow($row);
                        $this->acceptPayment($row, $this->gateway->createPayment(json_decode($row['request_payload'], true, 512, JSON_THROW_ON_ERROR), $row['id']));
                    }
                } else {
                    if ($row['provider_id']) {
                        $verified = $this->gateway->refund($row['provider_id']);
                    } else {
                        $this->assertRetryWindow($row);
                        $verified = $this->gateway->createRefund(json_decode($row['request_payload'], true, 512, JSON_THROW_ON_ERROR), $row['id']);
                    }
                    $this->acceptRefund($row, $verified);
                }
                $results[] = ['id' => $row['id'], 'checked' => true];
            } catch (Throwable) {
                $results[] = ['id' => $row['id'], 'checked' => false];
            } finally {
                // Every attempted row rotates, including persistent transport/provider failures.
                $this->db->update('payment_operations', ['updated_at' => gmdate('Y-m-d H:i:s')], ['id' => $row['id']]);
            }
        }
        return $results;
    }

    /** Durable outbox only. Safe inside the delivery transaction; no provider calls. */
    public function queueSettlementReceipt(string $orderId): void
    {
        $this->program->atomic(function () use ($orderId): void {
            $order = $this->db->fetchAssociative('SELECT * FROM orders WHERE id=?', [$orderId]);
            if (($order['delivery_method'] ?? null) === 'ozon-pickup'
                && (!$this->isDelivered($orderId)
                    || $this->db->fetchOne('SELECT 1 FROM ozon_delivered_postings WHERE order_id=? LIMIT 1', [$orderId]) !== false)) {
                return;
            }
            $id = hash('sha256', 'delivery-receipt:' . $orderId);
            if ($this->db->fetchOne('SELECT id FROM payment_operations WHERE id=?', [$id]) !== false) {
                return;
            }
            $payment = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE order_id=? AND kind='payment' AND status='succeeded' LIMIT 1", [$orderId]);
            if (!$payment) {
                return;
            }
            $original = json_decode($payment['request_payload'], true, 512, JSON_THROW_ON_ERROR);
            // No automatic fiscal assumptions for payments created without an advance receipt.
            if (!isset($original['receipt'])) {
                return;
            }
            $now = gmdate('Y-m-d H:i:s');
            $this->db->insert('payment_operations', ['id' => $id, 'order_id' => $orderId, 'kind' => 'receipt', 'provider_id' => null, 'amount_minor' => 0, 'status' => 'queued', 'request_payload' => '{}', 'confirmation_url' => null, 'created_at' => $now, 'updated_at' => $now]);
        });
    }

    /** Called inside the handover transaction. Each posting has one immutable fiscal operation. */
    public function queueOzonPostingReceipt(string $orderId, string $posting, array $lines): string
    {
        $id = hash('sha256', 'ozon-posting-receipt:' . $orderId . ':' . $posting);
        if ($this->db->fetchOne('SELECT 1 FROM payment_operations WHERE id=?', [$id]) !== false) {
            return $id;
        }
        $payment = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE order_id=? AND kind='payment' AND status='succeeded' AND provider_id IS NOT NULL LIMIT 1", [$orderId]);
        $order = $this->db->fetchAssociative('SELECT * FROM orders WHERE id=?', [$orderId]);
        if (!$payment || !$order) {
            throw new DomainExceptionModule('payment', 'Для чека отправления нужна подтверждённая предоплата.', 35);
        }
        $total = array_sum(array_map(static fn (array $line): int => $line['amountMinor'] * $line['quantity'], $lines));
        $payload = $total > 0 ? $this->settlementPayload($order, $payment, $lines, $total) : [];
        $now = gmdate('Y-m-d H:i:s');
        $this->db->insert('payment_operations', [
            'id' => $id, 'order_id' => $orderId, 'kind' => 'receipt', 'provider_id' => null,
            'amount_minor' => $total, 'status' => $total > 0 ? 'creating' : 'skipped',
            'request_payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'confirmation_url' => null,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        return $id;
    }

    /** Send/verify outside the financial mutex. Errors stay visible and never undo delivery. */
    public function settlementReceipt(string $orderId, ?string $operationId = null): array
    {
        if ($operationId === null) {
            $this->queueSettlementReceipt($orderId);
            $ids = $this->db->fetchFirstColumn("SELECT id FROM payment_operations WHERE order_id=? AND kind='receipt' ORDER BY created_at,id", [$orderId]);
            $first = null;
            $pending = null;
            foreach ($ids as $id) {
                $receipt = $this->settlementReceipt($orderId, $id);
                $first ??= $receipt;
                if (!\in_array($receipt['status'], ['succeeded', 'skipped'], true)) {
                    $pending ??= $receipt;
                }
            }
            return $pending ?? $first ?? ['id' => hash('sha256', 'delivery-receipt:' . $orderId), 'status' => 'not_required'];
        }
        $id = $operationId;
        try {
            $operation = $this->program->atomic(function () use ($id, $orderId): array|false {
                $row = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE id=? AND order_id=? AND kind='receipt'", [$id, $orderId]);
                if (!$row || \in_array($row['status'], ['succeeded', 'canceled', 'skipped', 'review_required'], true)) {
                    return $row;
                }
                if ($row['request_payload'] !== '{}' && json_decode($row['request_payload'], true, 512, JSON_THROW_ON_ERROR) !== []) {
                    return $row;
                }
                if (!$this->isDelivered($orderId)) {
                    return $row;
                }
                if ($this->db->fetchOne("SELECT id FROM program_refunds WHERE order_id=? AND status='pending' LIMIT 1", [$orderId]) !== false) {
                    $this->db->update('payment_operations', ['status' => 'waiting_refund', 'updated_at' => gmdate('Y-m-d H:i:s')], ['id' => $id]);
                    $row['status'] = 'waiting_refund';
                    return $row;
                }
                $payment = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE order_id=? AND kind='payment' AND status='succeeded' LIMIT 1", [$orderId]);
                $raw = $this->db->fetchOne('SELECT snapshot FROM program_orders WHERE id=?', [$orderId]);
                if ($raw === false) {
                    throw new DomainExceptionModule('payment', 'Для чека доставки нужен исходный снимок заказа.', 29);
                }
                $snapshot = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                $rows = [];
                $total = 0;
                foreach ($snapshot['items'] as $item) {
                    $refunded = (int)$item['refundedQuantity'];
                    $quantity = (int)$item['quantity'] - $refunded;
                    if ($quantity <= 0) {
                        continue;
                    }
                    $amount = (int)$item['paidMinor'] - intdiv((int)$item['paidMinor'] * $refunded, (int)$item['quantity']);
                    $total += $amount;
                    $rows[] = ['itemId' => $item['itemId'], 'description' => $item['description'], 'quantity' => $quantity, 'amountMinor' => $amount];
                }
                if (!$snapshot['deliveryRefunded'] && $snapshot['deliveryMinor'] > 0) {
                    $total += (int)$snapshot['deliveryMinor'];
                    $rows[] = ['itemId' => null, 'description' => 'Доставка', 'quantity' => 1, 'amountMinor' => (int)$snapshot['deliveryMinor']];
                }
                if ($total === 0) {
                    $this->db->update('payment_operations', ['status' => 'skipped'], ['id' => $id]);
                    $row['status'] = 'skipped';
                    return $row;
                }
                $order = $this->db->fetchAssociative('SELECT * FROM orders WHERE id=?', [$orderId]);
                $marks = ($order['delivery_method'] ?? null) === 'ozon-pickup'
                    ? array_map(static fn (array $line): array => $line['marks'], $this->ozonFiscalItems($orderId)) : [];
                $payload = $this->settlementPayload($order, $payment, $this->refundReceiptItems($rows, $marks), $total);
                $row = array_replace($row, ['status' => 'creating', 'amount_minor' => $total, 'request_payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')]);
                $this->db->update('payment_operations', array_diff_key($row, ['id' => true]), ['id' => $id]);
                return $row;
            });
            if (!$operation) {
                return ['id' => $id, 'status' => 'not_required'];
            }
            if (\in_array($operation['status'], ['queued', 'waiting_refund', 'succeeded', 'canceled', 'skipped', 'review_required'], true)) {
                return ['id' => $id, 'status' => $operation['status']];
            }
            if ($operation['provider_id'] === null) {
                try {
                    $this->assertRetryWindow($operation);
                } catch (Throwable) {
                    $this->db->update('payment_operations', ['status' => 'review_required'], ['id' => $id]);
                    return ['id' => $id, 'status' => 'review_required'];
                }
                $created = $this->gateway->createReceipt(json_decode($operation['request_payload'], true, 512, JSON_THROW_ON_ERROR), $id);
                $this->program->atomic(function () use ($id, $created): void {
                    $existing = $this->db->fetchOne('SELECT provider_id FROM payment_operations WHERE id=?', [$id]);
                    if ($existing !== null && $existing !== $created['id']) {
                        throw new DomainExceptionModule('payment', 'Конфликт идентификатора чека.', 30);
                    }
                    if ($this->db->fetchOne("SELECT id FROM payment_operations WHERE kind='receipt' AND provider_id=? AND id<>? LIMIT 1", [$created['id'], $id]) !== false) {
                        throw new DomainExceptionModule('payment', 'ЮKassa вернула идентификатор другого чека.', 30);
                    }
                    $this->db->executeStatement("UPDATE payment_operations SET provider_id=?,status='pending' WHERE id=? AND status NOT IN ('succeeded','canceled')", [$created['id'], $id]);
                });
                $operation['provider_id'] = $created['id'];
            }
            $verified = $this->gateway->receiptStatus($operation['provider_id']);
            $this->acceptReceipt($operation, $verified);
            return ['id' => $id, 'status' => $verified['status']];
        } catch (Throwable) {
            $this->db->executeStatement("UPDATE payment_operations SET status='retry_required',updated_at=? WHERE id=? AND order_id=? AND kind='receipt' AND status NOT IN ('succeeded','canceled','review_required')", [gmdate('Y-m-d H:i:s'), $id, $orderId]);
            return ['id' => $id, 'status' => 'retry_required'];
        }
    }

    private function settlementPayload(array $order, array $payment, array $items, int $total): array
    {
        $advance = json_decode($payment['request_payload'], true, flags: JSON_THROW_ON_ERROR)['receipt'] ?? null;
        $fiscal = $this->gateway->receipt($order, $items, 'full_payment');
        if ($advance === null || $fiscal === null) {
            throw new DomainExceptionModule('payment', 'Для чека выдачи нужна зарегистрированная предоплата и настроенная касса.', 35);
        }
        foreach ($fiscal['items'] as &$line) {
            $line['vat_code'] = $advance['items'][0]['vat_code'] ?? 1;
        }
        unset($line, $fiscal['tax_system_code']);
        if (isset($advance['tax_system_code'])) {
            $fiscal['tax_system_code'] = $advance['tax_system_code'];
        }
        $fiscal['customer'] = $advance['customer'];
        return $fiscal + [
            'type' => 'payment', 'payment_id' => $payment['provider_id'], 'send' => true,
            'settlements' => [['type' => 'prepayment', 'amount' => ['value' => YooKassaGateway::money($total), 'currency' => 'RUB']]],
        ];
    }

    /** Link an already registered provider receipt; never create a replacement blindly. */
    public function recoverSettlementReceipt(string $orderId, string $providerReceiptId, string $reason, int $actor, ?string $operationId = null): array
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', $providerReceiptId) || trim($reason) === '' || \strlen($reason) > 2000) {
            throw new DomainExceptionModule('payment', 'Нужны идентификатор существующего чека и причина сверки.', 33, status: 422);
        }
        $id = $operationId ?? hash('sha256', 'delivery-receipt:' . $orderId);
        $operation = $this->db->fetchAssociative("SELECT * FROM payment_operations WHERE id=? AND order_id=? AND kind='receipt'", [$id, $orderId]);
        if (!$operation || !\in_array($operation['status'], ['canceled', 'review_required', 'retry_required'], true) || !isset(json_decode($operation['request_payload'], true)['payment_id'])) {
            throw new DomainExceptionModule('payment', 'Чек не ожидает ручной сверки.', 34, status: 409);
        }
        $verified = $this->gateway->receiptStatus($providerReceiptId);
        if (($verified['status'] ?? '') !== 'succeeded') {
            throw new DomainExceptionModule('payment', 'ЮKassa не подтвердила регистрацию этого чека.', 35, status: 409);
        }
        $candidate = array_replace($operation, ['provider_id' => $providerReceiptId]);
        $this->assertReceipt($candidate, $verified);
        return $this->program->atomic(function () use ($id, $operation, $providerReceiptId, $reason, $actor, $orderId): array {
            $current = $this->db->fetchAssociative('SELECT * FROM payment_operations WHERE id=?', [$id]);
            if ($current['status'] === 'succeeded' && $current['provider_id'] === $providerReceiptId) {
                return ['id' => $id, 'status' => 'succeeded'];
            }
            if (!\in_array($current['status'], ['canceled', 'review_required', 'retry_required'], true) || $current['request_payload'] !== $operation['request_payload']) {
                throw new DomainExceptionModule('payment', 'Состояние чека изменилось. Повторите сверку.', 36, status: 409);
            }
            if ($this->db->fetchOne("SELECT id FROM payment_operations WHERE kind='receipt' AND provider_id=? AND id<>? LIMIT 1", [$providerReceiptId, $id]) !== false) {
                throw new DomainExceptionModule('payment', 'Этот чек уже связан с другой оплатой или отправлением.', 36, status: 409);
            }
            $this->db->update('payment_operations', ['provider_id' => $providerReceiptId, 'status' => 'succeeded', 'updated_at' => gmdate('Y-m-d H:i:s')], ['id' => $id]);
            $this->db->insert('program_audit', ['id' => bin2hex(random_bytes(16)), 'actor_id' => $actor, 'kind' => 'receipt_recovery', 'payload' => json_encode(['orderId' => $orderId, 'operationId' => $id, 'oldProviderId' => $current['provider_id'], 'providerReceiptId' => $providerReceiptId, 'reason' => trim($reason)], JSON_THROW_ON_ERROR), 'created_at' => gmdate('Y-m-d H:i:s')]);
            return ['id' => $id, 'status' => 'succeeded'];
        });
    }

    private function assertReceipt(array $operation, array $verified): void
    {
        $payload = json_decode($operation['request_payload'], true, 512, JSON_THROW_ON_ERROR);
        if (($verified['id'] ?? '') !== $operation['provider_id'] || ($verified['payment_id'] ?? '') !== $payload['payment_id'] || ($verified['type'] ?? '') !== 'payment' || !\in_array($verified['status'], ['pending', 'succeeded', 'canceled'], true)) {
            throw new DomainExceptionModule('payment', 'Чек не соответствует платежу.', 31);
        }
        $canonical = static function (array $items): array {
            $result = [];
            foreach ($items as $item) {
                $result[] = [$item['description'] ?? '', (string)(float)($item['quantity'] ?? 0), $item['amount']['currency'] ?? '', YooKassaGateway::minor((string)($item['amount']['value'] ?? '')), $item['payment_mode'] ?? '', $item['payment_subject'] ?? '', (int)($item['vat_code'] ?? 0), $item['mark_code_info']['gs_1m'] ?? null];
            }return $result;
        };
        if ($canonical($verified['items'] ?? []) !== $canonical($payload['items']) || ($verified['settlements'][0]['type'] ?? '') !== 'prepayment' || \count($verified['settlements'] ?? []) !== 1 || ($verified['settlements'][0]['amount']['currency'] ?? '') !== 'RUB' || YooKassaGateway::minor((string)($verified['settlements'][0]['amount']['value'] ?? '')) !== (int)$operation['amount_minor']) {
            throw new DomainExceptionModule('payment', 'Состав или сумма чека не совпадает.', 32);
        }
    }

    private function acceptReceipt(array $operation, array $verified): void
    {
        $this->assertReceipt($operation, $verified);
        $this->program->atomic(function () use ($operation, $verified): void {
            $this->db->executeStatement("UPDATE payment_operations SET status=?,updated_at=? WHERE id=? AND provider_id=? AND status NOT IN ('succeeded','canceled')", [$verified['status'], gmdate('Y-m-d H:i:s'), $operation['id'], $operation['provider_id']]);
        });
    }

    private function isDelivered(string $orderId): bool
    {
        $date = $this->db->fetchOne('SELECT delivered_at FROM program_orders WHERE id=?', [$orderId]);
        return $date !== false && $date !== null;
    }

    private function lockSql(string $sql): string
    {
        return $sql . ($this->db->getDatabasePlatform() instanceof SQLitePlatform ? '' : ' FOR UPDATE');
    }

    private function refresh(array $operation): array
    {
        $this->acceptPayment($operation, $this->gateway->payment($operation['provider_id']));
        return $this->status($operation['order_id'], false);
    }

    private function acceptPayment(array $operation, array $verified): void
    {
        $this->assertAmount($operation, $verified);
        if (($verified['metadata']['operation_id'] ?? '') !== $operation['id'] || ($verified['metadata']['order_id'] ?? '') !== $operation['order_id']) {
            throw new DomainExceptionModule('payment', 'Платёж не соответствует заказу.', 15, status: 409);
        }
        $this->program->atomic(function () use ($operation, $verified): void {
            $order = $this->db->fetchAssociative($this->lockSql('SELECT * FROM orders WHERE id=?'), [$operation['order_id']]);
            if (($order['delivery_method'] ?? null) === 'ozon-pickup' && ($verified['test'] ?? null) !== false) {
                throw new DomainExceptionModule('payment', 'Для доставки Ozon требуется боевой платёж ЮKassa.', 6, status: 503);
            }
            $stored = $this->db->fetchAssociative($this->lockSql('SELECT * FROM payment_operations WHERE id=?'), [$operation['id']]);
            if ($stored['provider_id'] !== null && $stored['provider_id'] !== $verified['id']) {
                throw new DomainExceptionModule('payment', 'Конфликт идентификатора платежа.', 16);
            }
            if (\in_array($stored['status'], ['succeeded', 'canceled'], true)) {
                return;
            }
            $status = $verified['status'];
            if (!\in_array($status, ['pending', 'waiting_for_capture', 'succeeded', 'canceled'], true)) {
                throw new DomainExceptionModule('payment', 'Неизвестный статус платежа.', 17);
            }
            $url = $verified['confirmation']['confirmation_url'] ?? null;
            if ($url !== null && (!\is_string($url) || !str_starts_with($url, 'https://'))) {
                throw new DomainExceptionModule('payment', 'Некорректная ссылка оплаты.', 18);
            }
            $this->db->update('payment_operations', ['provider_id' => $verified['id'], 'status' => $status, 'confirmation_url' => $url ?? $stored['confirmation_url'], 'updated_at' => gmdate('Y-m-d H:i:s')], ['id' => $stored['id']]);
            if ($status === 'succeeded') {
                if (($verified['paid'] ?? false) !== true) {
                    throw new DomainExceptionModule('payment', 'Списание платежа не подтверждено.', 19);
                }
                $this->db->executeStatement("UPDATE orders SET payment_status='completed', paid_at=COALESCE(paid_at,UTC_TIMESTAMP()),updated_at=UTC_TIMESTAMP() WHERE id=?", [$operation['order_id']]);
                $this->program->settleOrder($operation['order_id']);
                if (($order['delivery_method'] ?? null) === 'ozon-pickup') {
                    $this->db->executeStatement("UPDATE ozon_delivery_orders SET status='queued', updated_at=UTC_TIMESTAMP()
                        WHERE order_id=? AND mode='live' AND status='awaiting_payment'", [$operation['order_id']]);
                }
            } elseif ($status === 'canceled') {
                $this->db->executeStatement("UPDATE orders SET payment_status='failed',updated_at=UTC_TIMESTAMP() WHERE id=? AND payment_status<>'completed'", [$operation['order_id']]);
            }
        });
    }

    private function acceptRefund(array $operation, array $verified): void
    {
        $this->assertAmount($operation, $verified);
        $payload = json_decode($operation['request_payload'], true, 512, JSON_THROW_ON_ERROR);
        if (($verified['payment_id'] ?? '') !== $payload['payment_id']) {
            throw new DomainExceptionModule('payment', 'Возврат относится к другому платежу.', 23);
        }
        $this->program->atomic(function () use ($operation, $verified): void {
            $this->db->fetchOne($this->lockSql('SELECT id FROM orders WHERE id=?'), [$operation['order_id']]);
            $stored = $this->db->fetchAssociative($this->lockSql('SELECT * FROM payment_operations WHERE id=?'), [$operation['id']]);
            if ($stored['provider_id'] !== null && $stored['provider_id'] !== $verified['id']) {
                throw new DomainExceptionModule('payment', 'Конфликт идентификатора возврата.', 24);
            }
            if (\in_array($stored['status'], ['succeeded', 'canceled'], true)) {
                return;
            }
            if (!\in_array($verified['status'], ['pending', 'succeeded', 'canceled'], true)) {
                throw new DomainExceptionModule('payment', 'Неизвестный статус возврата.', 25);
            }
            $this->db->update('payment_operations', ['provider_id' => $verified['id'], 'status' => $verified['status'], 'updated_at' => gmdate('Y-m-d H:i:s')], ['id' => $operation['id']]);
            if ($verified['status'] === 'succeeded') {
                $this->program->completeRefund($operation['id']);
            }
            if ($verified['status'] === 'canceled') {
                $this->program->cancelRefund($operation['id']);
            }
        });
    }

    private function assertAmount(array $operation, array $verified): void
    {
        if (($verified['amount']['currency'] ?? '') !== 'RUB' || YooKassaGateway::minor((string)($verified['amount']['value'] ?? '')) !== (int)$operation['amount_minor']) {
            throw new DomainExceptionModule('payment', 'Сумма операции не совпадает с заказом.', 26);
        }
    }

    private function assertRetryWindow(array $operation): void
    {
        // Provider remembers keys for 24h. Never silently create another operation after ambiguity expires.
        if (strtotime($operation['created_at'] . ' UTC') < time() - 23 * 3600) {
            throw new DomainExceptionModule('payment', 'Нужна ручная сверка операции в ЮKassa: срок безопасного повтора истёк.', 27);
        }
    }

    private function refundReceiptItems(array $items, array $marks = [], array $fiscalUnits = []): array
    {
        $result = [];
        foreach ($items as $item) {
            $qty = max(1, (int)$item['quantity']);
            $total = (int)$item['amountMinor'];
            $unit = intdiv($total, $qty);
            $extra = $total % $qty;
            $base = ['description' => $item['description'], 'delivery' => $item['itemId'] === null];
            if (isset($fiscalUnits[$item['itemId']])) {
                foreach ($fiscalUnits[$item['itemId']] as $unit) {
                    $result[] = $base + ['amountMinor' => $unit['amountMinor'], 'quantity' => 1, 'mark' => $unit['mark']];
                }
                continue;
            }
            $codes = $marks[$item['itemId']] ?? [];
            if ($codes !== []) {
                if (count($codes) !== $qty || $unit <= 0) {
                    throw new DomainExceptionModule('payment', 'Не удалось разложить сумму по кодам маркировки заказа.', 35);
                }
                foreach ($codes as $index => $code) {
                    $result[] = $base + ['amountMinor' => $unit + (int)($index >= $qty - $extra), 'quantity' => 1, 'mark' => $code];
                }
                continue;
            }
            if ($qty - $extra > 0 && $unit > 0) {
                $result[] = $base + ['amountMinor' => $unit, 'quantity' => $qty - $extra];
            }
            if ($extra > 0) {
                $result[] = $base + ['amountMinor' => $unit + 1, 'quantity' => $extra];
            }
        }
        return $result;
    }

    private function ozonFiscalItems(string $orderId): array
    {
        $raw = $this->db->fetchOne("SELECT fiscal_items FROM ozon_delivery_orders WHERE order_id=? AND mode='live'", [$orderId]);
        return is_string($raw) ? json_decode($raw, true, flags: JSON_THROW_ON_ERROR) : [];
    }

    private function selectRefundUnits(string $orderId, array $requested, array $fiscal): array
    {
        if ($fiscal === [] || !isset(reset($fiscal)['units'])) {
            return [];
        }
        $used = [];
        foreach ($this->db->fetchFirstColumn("SELECT payload FROM program_refunds WHERE order_id=? AND status<>'cancelled'", [$orderId]) as $raw) {
            $payload = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            foreach ($payload['fiscalUnits'] ?? [] as $keys) {
                $used += array_fill_keys($keys, true);
            }
        }
        $result = [];
        foreach ($requested as $item) {
            $id = $item['itemId'] ?? null;
            $quantity = $item['quantity'] ?? null;
            if (!is_int($id) || !is_int($quantity) || $quantity < 1) {
                continue; // The refund ledger validates the request.
            }
            $available = array_values(array_filter($fiscal[$id]['units'] ?? [], static fn (array $unit): bool => !isset($used[$unit['key']])));
            if ($quantity > count($available)) {
                throw new DomainExceptionModule('payment', 'Указано больше экземпляров, чем доступно для возврата.', 35, status: 422);
            }
            if ($available[0]['mark'] === null) {
                $result[$id] = array_slice($available, 0, $quantity);
                continue;
            }
            $submitted = $item['markCodes'] ?? [];
            if ($submitted === [] && count($available) === $quantity) {
                $result[$id] = $available;
                continue;
            }
            $codes = is_array($submitted) && count(array_filter($submitted, 'is_string')) === count($submitted)
                ? array_unique(array_map(YooKassaGateway::base64Mark(...), $submitted)) : [];
            $selected = array_values(array_filter($available, static fn (array $unit): bool => in_array($unit['mark'], $codes, true)));
            if (count($selected) !== $quantity || count($codes) !== $quantity) {
                throw new DomainExceptionModule('payment', 'Укажите коды маркировки именно возвращаемых экземпляров.', 35, status: 422);
            }
            $result[$id] = $selected;
        }
        return $result;
    }

    private function selectRefundMarks(string $orderId, array $requested): array
    {
        $issued = $this->ozonFiscalItems($orderId);
        $used = [];
        foreach ($this->db->fetchFirstColumn("SELECT request_payload FROM payment_operations WHERE order_id=? AND kind='refund' AND status<>'canceled'", [$orderId]) as $raw) {
            $payload = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            foreach ($payload['receipt']['items'] ?? [] as $line) {
                if (isset($line['mark_code_info']['gs_1m'])) {
                    $used[] = $line['mark_code_info']['gs_1m'];
                }
            }
        }
        $result = [];
        foreach ($requested as $item) {
            $id = $item['itemId'];
            $codes = $issued[$id]['marks'] ?? [];
            if ($codes === []) {
                continue;
            }
            $available = array_values(array_diff(array_map(YooKassaGateway::base64Mark(...), $codes), $used));
            $submitted = $item['markCodes'] ?? [];
            if (is_array($submitted) && $submitted === [] && count($available) === $item['quantity']) {
                $result[$id] = $available;
                continue;
            }
            $selected = is_array($submitted) && count(array_filter($submitted, 'is_string')) === count($submitted)
                ? array_map(YooKassaGateway::base64Mark(...), array_values($submitted)) : [];
            if (count(array_unique($selected)) !== $item['quantity'] || array_diff($selected, $available) !== []) {
                throw new DomainExceptionModule('payment', 'Укажите коды маркировки именно возвращаемых экземпляров.', 35, status: 422);
            }
            $result[$id] = $selected;
        }
        return $result;
    }

    private function receiptItems(array $order): array
    {
        $raw = $this->db->fetchOne('SELECT snapshot FROM program_orders WHERE id=?', [$order['id']]);
        if ($raw !== false) {
            $snapshot = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            $rows = array_map(static fn (array $i): array => ['itemId' => $i['itemId'], 'description' => $i['description'], 'amountMinor' => $i['paidMinor'], 'quantity' => $i['quantity']], $snapshot['items']);
            if ($snapshot['deliveryMinor'] > 0) {
                $rows[] = ['itemId' => null, 'description' => 'Доставка', 'amountMinor' => $snapshot['deliveryMinor'], 'quantity' => 1];
            }
            return $this->refundReceiptItems($rows);
        }
        $items = $this->db->fetchAllAssociative('SELECT * FROM order_items WHERE order_id=? ORDER BY id', [$order['id']]);
        $subtotal = array_sum(array_map(static fn (array $i): int => (int)$i['price'] * (int)$i['quantity'] * 100, $items));
        $deliveryMinor = ProgramMath::minor($order['delivery_cost']);
        $paid = max(0, ProgramMath::minor($order['total']) - $deliveryMinor);
        $result = [];
        $cursor = 0;
        $allocated = 0;
        foreach ($items as $item) {
            $cursor += (int)$item['price'] * (int)$item['quantity'] * 100;
            $target = $subtotal > 0 ? intdiv($paid * $cursor, $subtotal) : 0;
            $line = $target - $allocated;
            $allocated = $target;
            $qty = (int)$item['quantity'];
            $unit = intdiv($line, max(1, $qty));
            $remainder = $line % max(1, $qty);
            if ($qty - $remainder > 0 && $unit > 0) {
                $result[] = ['description' => $item['product_name'], 'amountMinor' => $unit, 'quantity' => $qty - $remainder];
            }
            if ($remainder > 0) {
                $result[] = ['description' => $item['product_name'], 'amountMinor' => $unit + 1, 'quantity' => $remainder];
            }
        }
        if ($deliveryMinor > 0) {
            $result[] = ['description' => 'Доставка', 'amountMinor' => $deliveryMinor, 'quantity' => 1, 'delivery' => true];
        }
        return $result;
    }
}
