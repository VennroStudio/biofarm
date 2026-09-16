<?php

declare(strict_types=1);

namespace App\Modules\Order\Service;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Order\Entity\Order\Order;
use Doctrine\DBAL\Connection;

final readonly class OrderStatusGuard
{
    /** @var list<string> */
    private const array ORDER_STATUSES = [
        'pending',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
    ];

    /** @var list<string> */
    private const array PAYMENT_STATUSES = [
        'pending',
        'completed',
        'failed',
        'refunded',
    ];

    public function __construct(private Connection $db) {}

    public function orderStatus(string $status): string
    {
        $status = trim($status);
        if (!\in_array($status, self::ORDER_STATUSES, true)) {
            throw new DomainExceptionModule(
                module: 'order',
                message: 'error.invalid_order_status',
                code: 31,
                status: 422,
            );
        }

        return $status;
    }

    public function paymentStatus(string $status): string
    {
        $status = trim($status);
        if (!\in_array($status, self::PAYMENT_STATUSES, true)) {
            throw new DomainExceptionModule(
                module: 'order',
                message: 'error.invalid_payment_status',
                code: 32,
                status: 422,
            );
        }

        return $status;
    }

    /** Caller holds ProgramService::atomic mutex before reading/editing the order. */
    public function adminTransition(Order $order, string $status, string $paymentStatus): void
    {
        $this->orderStatus($status);
        $this->paymentStatus($paymentStatus);
        $stored = $this->db->fetchAssociative('SELECT status,payment_status FROM orders WHERE id=?', [$order->id]);
        if ($stored === false) {
            throw $this->conflict('Заказ не найден.');
        }
        if ($stored['status'] !== $order->status || $stored['payment_status'] !== $order->paymentStatus) {
            throw $this->conflict('Статус заказа изменился. Обновите страницу.');
        }
        if (\in_array($stored['payment_status'], ['completed', 'refunded'], true) && $paymentStatus !== $stored['payment_status']) {
            throw $this->conflict('Подтверждённую оплату нельзя сбросить. Используйте возврат по позициям.');
        }
        if ($paymentStatus === 'refunded' && $stored['payment_status'] !== 'refunded') {
            throw $this->conflict('Статус возврата устанавливается только после подтверждения возврата.');
        }
        $snapshotStatus = $this->db->fetchOne('SELECT status FROM program_orders WHERE id=?', [$order->id]);
        if (($snapshotStatus === 'cancelled' || $stored['status'] === 'cancelled') && ($status !== $stored['status'] || $paymentStatus !== $stored['payment_status'])) {
            throw $this->conflict('Отменённый заказ нельзя открыть повторно. Создайте новый заказ.');
        }
        $activePayment = $this->db->fetchOne("SELECT id FROM payment_operations WHERE order_id=? AND kind='payment' AND status<>'canceled' LIMIT 1", [$order->id]);
        if ($activePayment !== false && ($paymentStatus !== $stored['payment_status'] || ($status === 'cancelled' && $status !== $stored['status']))) {
            throw $this->conflict('Сначала проверьте или завершите текущий платёж ЮKassa. Ручная смена оплаты и отмена сейчас недоступны.');
        }
        if ($status === 'cancelled' && \in_array($paymentStatus, ['completed', 'refunded'], true) && $stored['status'] !== 'cancelled') {
            throw $this->conflict('Оплаченный заказ требует подтверждённого возврата.');
        }
        if ($status === 'delivered' && $paymentStatus !== 'completed' && $stored['status'] !== 'delivered') {
            throw $this->conflict('Доставку можно подтвердить только для оплаченного заказа.');
        }
    }

    /** Repeated unchanged financial fields from the full form are accepted. */
    public function adminFinancialTerms(Order $order, array $proposed, array $payload): void
    {
        $hasSnapshot = $this->db->fetchOne('SELECT id FROM program_orders WHERE id=?', [$order->id]) !== false;
        if ($hasSnapshot) {
            foreach ($proposed as $field => $value) {
                if ($order->{$field} !== $value) {
                    throw $this->conflict('Финансовые условия заказа зафиксированы. Отмените заказ и создайте новый.');
                }
            }
        }
        if (!\array_key_exists('items', $payload) || $payload['items'] === null) {
            return;
        }
        if (!\is_array($payload['items'])) {
            throw $this->conflict('Некорректные позиции заказа.');
        }
        $stored = $this->db->fetchAllAssociative('SELECT id,product_id,product_name,price,quantity FROM order_items WHERE order_id=? ORDER BY id', [$order->id]);
        if (\count($stored) !== \count($payload['items'])) {
            throw $this->conflict('Изменение позиций заказа недоступно.');
        }
        $remaining = $stored;
        foreach ($payload['items'] as $item) {
            if (!\is_array($item)) {
                throw $this->conflict('Некорректные позиции заказа.');
            }
            $matched = false;
            foreach ($remaining as $key => $row) {
                $idMatches = !isset($item['id']) || (int)$item['id'] === (int)$row['id'];
                if ($idMatches && (int)($item['productId'] ?? $item['product_id'] ?? 0) === (int)$row['product_id'] && (int)($item['quantity'] ?? 0) === (int)$row['quantity'] && (!isset($item['price']) || (int)$item['price'] === (int)$row['price']) && (!isset($item['productName']) && !isset($item['product_name']) || (string)($item['productName'] ?? $item['product_name']) === $row['product_name'])) {
                    unset($remaining[$key]);
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                throw $this->conflict('Изменение позиций заказа недоступно.');
            }
        }
    }

    private function conflict(string $message): DomainExceptionModule
    {
        return new DomainExceptionModule('order', $message, 33, status: 409);
    }
}
