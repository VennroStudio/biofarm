<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Cacher\Cacher;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\Payment\Service\YooKassaGateway;
use App\Modules\Program\Service\ProgramMath;
use App\Modules\Program\Service\ProgramService;
use Doctrine\DBAL\Connection;
use Throwable;

final readonly class OzonFulfillment
{
    public function __construct(
        private Connection $db,
        private OzonDelivery $delivery,
        private OzonConnection $connection,
        private PaymentService $payments,
        private ProgramService $program,
        private Cacher $cacher,
    ) {}

    public function tick(int $limit = 20): void
    {
        $limit = max(1, min(100, $limit));
        $states = $this->connection->deliveryConnected()
            ? "'queued','submitting','submitted','cancel_requested','cancel_sending','cancel_pending','cancel_confirmed','partial_refund'"
            : "'cancel_confirmed','partial_refund'";
        $orders = $this->db->fetchFirstColumn(
            "SELECT order_id FROM ozon_delivery_orders WHERE mode='live' AND status IN ($states)
             AND (next_check_at IS NULL OR next_check_at <= UTC_TIMESTAMP())
             ORDER BY COALESCE(next_check_at, updated_at), order_id LIMIT $limit",
        );
        foreach ($orders as $id) {
            $this->process($id);
        }
    }

    public function process(string $orderId): void
    {
        $this->locked($orderId, function (?array $row) use ($orderId): void {
            if ($row === null) {
                return;
            }
            $state = $row['status'];
            $delivery = $this->delivery;
            try {
                match ($state) {
                    'queued' => $this->submit($delivery, $row),
                    'submitting' => $this->update($orderId, ['status' => 'submit_review', 'error' => 'Подтвердите в Ozon, был ли создан заказ.']),
                    'submitted' => $this->track($delivery, $row),
                    'cancel_requested' => $this->submitCancellation($delivery, $row),
                    'cancel_sending', 'cancel_pending' => $this->checkCancellation($delivery, $row),
                    'cancel_confirmed', 'partial_refund' => $this->refund($row, $state === 'cancel_confirmed'),
                    default => null,
                };
            } catch (Throwable $e) {
                $current = $this->row($orderId);
                $message = $e instanceof OzonException || $e instanceof DomainExceptionModule
                    ? mb_substr($e->getMessage(), 0, 500)
                    : 'Операция доставки не завершилась. Проверьте состояние заказа.';
                $this->update($orderId, ['error' => $message] + match ($current['status'] ?? null) {
                    'submitting' => ['status' => 'submit_review'],
                    'cancel_sending' => ['status' => 'cancel_pending'],
                    default => [],
                });
            }
        });
    }

    public function resolveSubmission(string $orderId, string $number, bool $absent, string $reason, int $actor): array
    {
        return $this->locked($orderId, function (?array $row) use ($orderId, $number, $absent, $reason, $actor): array {
            if ($row === null || $row['status'] !== 'submit_review' || $row['external_number'] !== null) {
                throw new DomainExceptionModule('order', 'Ручная сверка создания для этого заказа недоступна.', 45, status: 409);
            }
            if (mb_strlen(trim($reason)) < 8 || mb_strlen($reason) > 500 || $absent === ($number !== '')
                || ($number !== '' && !preg_match('/^[0-9]{6,20}-[0-9]{3,8}$/D', $number))) {
                throw new DomainExceptionModule('order', 'Укажите итог сверки в Ozon и комментарий.', 45, status: 422);
            }
            $payload = json_decode($row['payload'], true, flags: JSON_THROW_ON_ERROR);
            $payload['manualResolution'] = ['actor' => $actor, 'reason' => trim($reason), 'date' => gmdate(DATE_ATOM)];
            $changes = ['payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'error' => null, 'next_check_at' => null];
            if ($absent) {
                $changes['status'] = 'cancel_confirmed';
                $this->update($orderId, $changes);
                return ['status' => $changes['status']];
            }
            $postings = $this->delivery->postings($number);
            $products = [];
            foreach ($postings as $posting) {
                if (($posting['order_number'] ?? '') !== $number || ($posting['external_order']['is_external'] ?? false) !== true) {
                    throw new DomainExceptionModule('order', 'Номер не относится к заказам с внешней витрины.', 45, status: 422);
                }
                array_push($products, ...($posting['products'] ?? []));
            }
            if ($this->quantities($products, 'sku') !== $this->quantities($payload['items'], 'sku')) {
                throw new DomainExceptionModule('order', 'Состав заказа Ozon не совпадает.', 45, status: 422);
            }
            $this->db->transactional(function () use ($orderId, $number, $changes): void {
                $this->update($orderId, $changes + ['status' => 'submitted', 'external_number' => $number]);
                $this->db->update('orders', ['tracking_number' => $number, 'status' => 'processing'], ['id' => $orderId]);
            });
            $this->invalidate($orderId);
            return ['status' => 'submitted'];
        }, failOnContention: true);
    }

    /** Administrative requests must confirm the logistics outcome before crediting a refund. */
    public function requestCancellation(string $orderId, array $items, bool $delivery): ?array
    {
        return $this->locked($orderId, function (?array $row) use ($orderId, $items, $delivery): ?array {
            if ($row === null || $row['status'] === 'delivered') {
                return null;
            }
            if (in_array($row['status'], ['cancel_requested', 'cancel_sending', 'cancel_pending', 'cancel_confirmed'], true)) {
                return ['status' => 'pending', 'ozonStatus' => $row['status']];
            }
            if ($row['status'] === 'cancelled') {
                return ['status' => 'succeeded', 'ozonStatus' => $row['status']];
            }
            if (!in_array($row['status'], ['queued','submitted'], true)) {
                throw new DomainExceptionModule('order', 'Сначала уточните состояние отправлений Ozon.', 45, status: 409);
            }
            $requested = $this->quantities($items, 'itemId');
            $whole = $this->quantities($this->refundItems($row, true), 'itemId');
            if (!$delivery || $whole === [] || $requested !== $whole) {
                throw new DomainExceptionModule('order', 'До выдачи можно отменить только заказ целиком с возвратом доставки.', 45, status: 422);
            }
            $state = $row['status'] === 'queued' ? 'cancel_confirmed' : 'cancel_requested';
            $this->update($orderId, ['status' => $state, 'error' => null, 'next_check_at' => null]);
            return ['status' => 'pending', 'ozonStatus' => $state];
        }, failOnContention: true);
    }

    private function submit(OzonDelivery $delivery, array $row): void
    {
        $id = $row['order_id'];
        $order = $this->db->fetchAssociative("SELECT o.shipping_address FROM orders o WHERE o.id=? AND o.payment_status='completed'
            AND o.delivery_method='ozon-pickup' AND EXISTS (
                SELECT 1 FROM payment_operations p WHERE p.order_id=? AND p.kind='payment' AND p.status='succeeded' AND p.provider_id IS NOT NULL
            )", [$id, $id]);
        if ($order === false) {
            throw new OzonException('Ждём подтверждение платежа ЮKassa.');
        }
        $payload = json_decode($row['payload'], true, flags: JSON_THROW_ON_ERROR);
        $shipping = json_decode($order['shipping_address'], true, flags: JSON_THROW_ON_ERROR);
        $phone = preg_replace('/\D/', '', $shipping['phone']);
        if (!$delivery->buyerAllowed($phone)) {
            $this->update($id, ['status' => 'cancel_confirmed', 'next_check_at' => null, 'error' => 'Ozon отклонил доставку для покупателя.']);
            return;
        }
        try {
            $splits = $delivery->quote($phone, $payload['destination'], $payload['items']);
        } catch (OzonException $exception) {
            if (!$exception->quoteUnavailable) {
                throw $exception;
            }
            $this->update($id, ['status' => 'cancel_confirmed', 'next_check_at' => null, 'error' => $exception->getMessage()]);
            return;
        }
        $dates = OzonDelivery::dates($splits);
        if (array_values(array_unique($dates)) !== array_values(array_unique($payload['dates']))
            || $delivery->quotedCostMinor($splits) !== $payload['deliveryCostMinor']) {
            $this->update($id, ['status' => 'cancel_confirmed', 'next_check_at' => null, 'error' => 'Стоимость или срок доставки изменились после оплаты.']);
            return;
        }
        [$firstName, $lastName] = array_pad(preg_split('/\s+/u', trim($shipping['name']), 2), 2, '');
        $buyer = ['phone' => $phone, 'email' => $shipping['email'], 'first_name' => $firstName, 'last_name' => $lastName];
        $recipient = ['recipient_phone' => $phone, 'recipient_first_name' => $firstName, 'recipient_last_name' => $lastName];
        $payload['dates'] = $dates;
        $this->update($id, ['status' => 'submitting', 'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'next_check_at' => gmdate('Y-m-d H:i:s', time() + 120)]);
        $result = $delivery->create($buyer, $recipient, $payload['destination'], $splits, $payload['prices']);
        $postings = [];
        foreach ($result['postings'] ?? [] as $posting) {
            $number = is_array($posting) ? ($posting['posting_number'] ?? null) : $posting;
            if (is_string($number) && $number !== '') {
                $postings[] = ['number' => $number, 'status' => '', 'substatus' => ''];
            }
        }
        $this->db->transactional(function () use ($id, $result, $postings): void {
            $this->update($id, ['status' => 'submitted', 'external_number' => $result['order_number'],
                'postings' => json_encode($postings, JSON_THROW_ON_ERROR), 'error' => null]);
            $this->db->update('orders', ['tracking_number' => $result['order_number'], 'status' => 'processing'], ['id' => $id]);
        });
        $this->invalidate($id);
    }

    private function track(OzonDelivery $delivery, array $row, bool $cancellationConfirmed = false): void
    {
        $id = $row['order_id'];
        $raw = $delivery->postings($row['external_number']);
        $postings = [];
        $received = [];
        $reported = [];
        $open = false;
        foreach ($raw as $posting) {
            if (($posting['order_number'] ?? '') !== $row['external_number'] || empty($posting['posting_number'])) {
                throw new OzonException('Ozon вернул отправление другого заказа.');
            }
            $number = $posting['posting_number'];
            if (isset($postings[$number])) {
                throw new OzonException('Ozon вернул повторное отправление.');
            }
            $status = $posting['status'] ?? '';
            $substatus = $posting['substatus'] ?? '';
            $postings[$number] = compact('number','status','substatus');
            array_push($reported, ...($posting['products'] ?? []));
            if (in_array($substatus, ['posting_received', 'posting_delivered'], true) || ($status === 'delivered' && $substatus === '')) {
                $received[$number] = $posting;
            } elseif (!in_array($status, ['cancelled','canceled'], true) && $substatus !== 'posting_canceled') {
                $open = true;
            }
        }
        if ($postings === []) {
            $this->update($id, []);
            return;
        }
        $previous = json_decode($row['postings'] ?? '[]', true, flags: JSON_THROW_ON_ERROR);
        $known = array_column($previous, null, 'number');
        $this->update($id, ['postings' => json_encode(array_values($postings + $known), JSON_THROW_ON_ERROR), 'error' => null]);

        $progress = $this->issuedUnits($id);
        $pendingHandover = null;
        foreach (array_diff_key($received, $progress) as $number => $posting) {
            try {
                $this->recordHandover($id, $number, $this->fiscalItems($delivery, $row, [$number => $posting]));
            } catch (OzonException|DomainExceptionModule $exception) {
                $pendingHandover ??= $exception;
            }
        }
        if ($pendingHandover !== null) {
            throw $pendingHandover;
        }
        if (array_diff_key($known, $postings) !== []) {
            throw new OzonException('Ozon вернул неполный список отправлений.');
        }
        if ($open) {
            return;
        }

        $payload = json_decode($row['payload'], true, flags: JSON_THROW_ON_ERROR);
        $planned = $this->quantities($payload['items'], 'sku');
        $seen = $this->quantities($reported, 'sku');
        if ($seen !== $planned) {
            // Ozon can add a canceled posting for a refusal while retaining the original
            // posting's quantities. All ordered units must still be accounted for.
            $coversOrder = array_all($planned, static fn (int $quantity, int $sku): bool => ($seen[$sku] ?? 0) >= $quantity);
            if ((!$coversOrder && !$cancellationConfirmed) || count($postings) < count($payload['dates'])) {
                throw new OzonException('Ozon пока не вернул полный состав заказа.');
            }
        }

        $issued = $this->flattenUnits($this->issuedUnits($id));
        $items = [];
        foreach ($this->db->fetchAllAssociative('SELECT id,quantity FROM order_items WHERE order_id=?', [$id]) as $item) {
            $units = $issued[$item['id']] ?? [];
            $qty = count($units);
            $items[(int)$item['id']] = [
                'quantity' => $qty, 'remaining' => (int)$item['quantity'] - $qty,
                'marks' => array_values(array_filter(array_column($units, 'mark'))),
                'units' => $units,
            ];
        }
        if (array_sum(array_column($items, 'quantity')) === 0) {
            $this->update($id, ['status' => 'cancel_confirmed', 'next_check_at' => null]);
            return;
        }
        $partial = array_sum(array_column($items, 'remaining')) > 0;
        $this->program->atomic(function () use ($id, $items, $partial): void {
            $this->update($id, ['status' => $partial ? 'partial_refund' : 'delivered',
                'fiscal_items' => json_encode($items, JSON_THROW_ON_ERROR), 'next_check_at' => null]);
            if (!$partial) {
                $this->program->deliverOrder($id);
                $this->payments->queueSettlementReceipt($id);
            }
        });
        if (!$partial) {
            $this->invalidate($id);
        }
    }

    private function recordHandover(string $id, string $posting, array $received): void
    {
        $this->program->atomic(function () use ($id, $posting, $received): void {
            $progress = $this->issuedUnits($id);
            if (isset($progress[$posting])) {
                return;
            }
            $previous = $this->flattenUnits($progress);
            $snapshot = json_decode((string)$this->db->fetchOne('SELECT snapshot FROM program_orders WHERE id=?', [$id]), true, flags: JSON_THROW_ON_ERROR);
            $usedMarks = [];
            foreach ($previous as $units) {
                foreach (array_filter(array_column($units, 'mark')) as $mark) {
                    $usedMarks[$mark] = true;
                }
            }
            $lines = [];
            $items = [];
            foreach ($snapshot['items'] as $item) {
                $itemId = $item['itemId'];
                $handover = $received[$itemId];
                $handed = count($previous[$itemId] ?? []);
                $total = (int)$item['quantity'];
                if ($handed + $handover['quantity'] > $total) {
                    throw new OzonException('Количество выданных экземпляров превышает состав заказа.');
                }
                for ($index = 0; $index < $handover['quantity']; ++$index) {
                    // The first units remain available to the advance refund for unissued products.
                    $price = ProgramMath::portion((int)$item['paidMinor'], $total - $handed - $index - 1, 1, $total);
                    $mark = isset($handover['marks'][$index]) ? YooKassaGateway::base64Mark($handover['marks'][$index]) : null;
                    if ($mark !== null && isset($usedMarks[$mark])) {
                        throw new OzonException('Ozon повторно передал код выданного экземпляра.');
                    }
                    if ($mark !== null) {
                        $usedMarks[$mark] = true;
                    }
                    $items[$itemId][] = ['key' => "$posting:$itemId:$index", 'amountMinor' => $price, 'mark' => $mark];
                    $lines[] = ['description' => $item['description'], 'quantity' => 1, 'amountMinor' => $price, 'mark' => $mark];
                }
            }
            if ($items !== [] && $previous === [] && $snapshot['deliveryMinor'] > 0) {
                $lines[] = ['description' => 'Доставка', 'quantity' => 1, 'amountMinor' => (int)$snapshot['deliveryMinor'], 'delivery' => true];
            }
            $receipt = $this->payments->queueOzonPostingReceipt($id, $posting, $lines);
            $this->db->insert('ozon_delivered_postings', [
                'order_id' => $id, 'posting_number' => $posting, 'items' => json_encode($items, JSON_THROW_ON_ERROR), 'receipt_operation_id' => $receipt,
            ]);
        });
    }

    private function issuedUnits(string $id): array
    {
        $rows = $this->db->fetchAllKeyValue('SELECT posting_number,items FROM ozon_delivered_postings WHERE order_id=?', [$id]);
        return array_map(static fn (string $items): array => json_decode($items, true, flags: JSON_THROW_ON_ERROR), $rows);
    }

    private function flattenUnits(array $postingUnits): array
    {
        $result = [];
        foreach ($postingUnits as $posting) {
            foreach ($posting as $itemId => $units) {
                $result[$itemId] = [...($result[$itemId] ?? []), ...$units];
            }
        }
        return $result;
    }

    private function fiscalItems(OzonDelivery $delivery, array $row, array $received): array
    {
        $orderId = $row['order_id'];
        $catalog = json_decode($row['payload'], true, flags: JSON_THROW_ON_ERROR)['catalogItems'];
        $returns = $delivery->partialReturns($row['external_number'], array_keys($received));
        $delivered = [];
        $marks = [];
        foreach ($received as $number => $posting) {
            $products = $this->quantities($posting['products'] ?? [], 'sku');
            if ($products === []) {
                throw new OzonException('Ozon не вернул товары выданного отправления.');
            }
            $expectedMarked = array_intersect_key($products, array_flip(array_column(array_filter($catalog, static fn (array $item): bool => $item['marked']), 'sku')));
            if ($expectedMarked !== []) {
                [$issued, $unissued] = $delivery->receivedExemplars($number);
                foreach ($expectedMarked as $sku => $qty) {
                    $codes = $issued[$sku] ?? [];
                    if (in_array(null, $codes, true) || count($codes) > $qty
                        || count($codes) + ($unissued[$sku] ?? 0) < $qty) {
                        throw new OzonException('Ozon пока не подтвердил маркировку всех экземпляров.');
                    }
                    // Marks are per instance and reflect partial handover, unlike the posting quantity.
                    $products[$sku] = count($codes);
                    $marks[$sku] = [...($marks[$sku] ?? []), ...$codes];
                }
            }
            foreach ($returns[$number] ?? [] as $sku => $quantity) {
                if (isset($expectedMarked[$sku])) {
                    continue;
                }
                if (($products[$sku] ?? 0) < $quantity) {
                    throw new OzonException('Не удалось сверить состав частичного невыкупа Ozon.');
                }
                $products[$sku] -= $quantity;
            }
            foreach ($products as $sku => $qty) {
                $delivered[$sku] = ($delivered[$sku] ?? 0) + $qty;
            }
        }
        $items = [];
        foreach ($this->db->fetchAllAssociative('SELECT id,product_id,quantity FROM order_items WHERE order_id=?', [$orderId]) as $item) {
            $product = $catalog[$item['product_id']];
            $sku = $product['sku'];
            $qty = $delivered[$sku] ?? 0;
            $codes = $marks[$sku] ?? [];
            if ($qty > (int)$item['quantity'] || ($product['marked'] && count(array_unique($codes)) !== $qty)) {
                throw new OzonException('Не удалось сверить состав выданного заказа Ozon.');
            }
            $items[(int)$item['id']] = ['quantity' => $qty, 'remaining' => (int)$item['quantity'] - $qty, 'marks' => $codes];
            unset($delivered[$sku]);
        }
        if ($delivered !== []) {
            throw new OzonException('Ozon не подтвердил состав выданного заказа.');
        }
        return $items;
    }

    private function submitCancellation(OzonDelivery $delivery, array $row): void
    {
        $reason = $delivery->cancellationReason($row['external_number']);
        if ($reason === null) {
            $this->update($row['order_id'], ['status' => 'submitted', 'error' => 'Ozon отклонил отмену заказа.']);
            return;
        }
        $this->update($row['order_id'], ['status' => 'cancel_sending']);
        $delivery->requestCancellation($row['external_number'], $reason);
        $this->update($row['order_id'], ['status' => 'cancel_pending','error' => null]);
    }

    private function checkCancellation(OzonDelivery $delivery, array $row): void
    {
        $result = $delivery->cancellationStatus($row['external_number']);
        if (($result['state'] ?? null) === 'Подтверждена') {
            // An already issued posting belongs in a settlement receipt even if Ozon canceled the other postings.
            $this->track($delivery, $row, cancellationConfirmed: true);
        }
        if (in_array($result['state'] ?? null, ['Отклонена', 'Не подтверждена'], true)) {
            $this->update($row['order_id'], ['status' => 'submitted', 'error' => 'Ozon отклонил отмену заказа.']);
        }
    }

    private function refund(array $row, bool $whole): void
    {
        $id = $row['order_id'];
        $requestId = $whole ? 'ozon-cancellation-v1' : 'ozon-partial-handover-v1';
        $receipt = $this->payments->requestRefund($id, $requestId, $this->refundItems($row, $whole), $whole, confirmedOzon: true);
        if ($receipt['status'] === 'canceled') {
            $this->update($id, ['status' => 'review', 'error' => 'ЮKassa отклонила возврат по заказу Ozon.']);
        }
        if ($receipt['status'] !== 'succeeded') {
            return;
        }
        $this->program->atomic(function () use ($id, $whole): void {
            $this->update($id, ['status' => $whole ? 'cancelled' : 'delivered', 'error' => null]);
            if ($whole) {
                $this->db->update('orders', ['status' => 'cancelled'], ['id' => $id]);
            } else {
                $this->program->deliverOrder($id);
                $this->payments->queueSettlementReceipt($id);
            }
        });
        $this->invalidate($id);
    }

    private function refundItems(array $row, bool $whole): array
    {
        $fiscal = $whole ? [] : json_decode($row['fiscal_items'], true, flags: JSON_THROW_ON_ERROR);
        $items = [];
        foreach ($this->db->fetchAllAssociative('SELECT id,quantity FROM order_items WHERE order_id=? ORDER BY id', [$row['order_id']]) as $item) {
            $quantity = $whole ? (int)$item['quantity'] : $fiscal[$item['id']]['remaining'];
            if ($quantity > 0) {
                $items[] = ['itemId' => (int)$item['id'], 'quantity' => $quantity];
            }
        }
        return $items;
    }

    private function quantities(array $rows, string $key): array
    {
        $totals = [];
        foreach ($rows as $row) {
            $id = OzonProduct::positiveId($row[$key] ?? null);
            $quantity = filter_var($row['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($id === null || $quantity === false || $quantity < 1) {
                throw new OzonException('Некорректное количество или идентификатор товара.');
            }
            $totals[$id] = ($totals[$id] ?? 0) + $quantity;
        }
        ksort($totals);
        return $totals;
    }

    private function row(string $id): ?array
    {
        $row = $this->db->fetchAssociative("SELECT * FROM ozon_delivery_orders WHERE order_id=? AND mode='live'", [$id]);
        return $row === false ? null : $row;
    }

    private function update(string $id, array $fields): void
    {
        $this->db->update('ozon_delivery_orders', $fields + [
            'updated_at' => gmdate('Y-m-d H:i:s'), 'next_check_at' => gmdate('Y-m-d H:i:s', time() + 60),
        ], ['order_id' => $id]);
    }

    private function invalidate(string $id): void
    {
        $this->cacher->deleteTag('orders');
        $this->cacher->delete('order_by_id_' . $id);
    }

    private function locked(string $id, callable $callback, bool $failOnContention = false): mixed
    {
        $lock = 'ozon-order-' . sha1((string)$this->db->getDatabase() . ':' . $id);
        if ((int)$this->db->fetchOne('SELECT GET_LOCK(?, ?)', [$lock, $failOnContention ? 2 : 0]) !== 1) {
            if ($failOnContention) {
                throw new DomainExceptionModule('order', 'Статус заказа обновляется. Повторите попытку.', 45, status: 409);
            }
            return null;
        }
        try {
            return $callback($this->row($id));
        } finally {
            $this->db->fetchOne('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }
}
