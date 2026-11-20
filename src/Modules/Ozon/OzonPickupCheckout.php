<?php

declare(strict_types=1);

namespace App\Modules\Ozon;

use App\Components\Setting\SiteSettings;
use App\Modules\Payment\Service\YooKassaGateway;
use Doctrine\DBAL\Connection;

use function App\Components\env_bool;

final readonly class OzonPickupCheckout
{
    public const string LIVE_PICKUP = 'ozon-pickup';

    public function __construct(
        private Connection $db,
        private SiteSettings $settings,
        private OzonDelivery $delivery,
        private OzonConnection $connection,
        private YooKassaGateway $payments,
    ) {}

    public function method(): ?string
    {
        return !$this->settings->bool('testing_enabled') && env_bool('OZON_DELIVERY_ENABLED', false)
            ? self::LIVE_PICKUP : null;
    }

    public function ready(): bool
    {
        if ($this->method() === null || !$this->connection->deliveryConnected()) {
            return false;
        }
        $payment = $this->payments->publicConfig();
        return $payment['configured'] && $payment['receiptsEnabled'];
    }

    private static function pickupAddress(array $point): array
    {
        return [
            'id' => (int) $point['map_point_id'],
            'address' => trim($point['address']),
            'city' => trim($point['address_details']['city'] ?? ''),
        ];
    }

    public function prepare(array $cart, string $phone, int $userId, mixed $pickupId): array
    {
        if (!$this->ready()) {
            throw new OzonException('Доставка Ozon временно недоступна.');
        }
        $allowed = $this->delivery->buyerAllowed($phone);
        $this->db->update('user_profiles', [
            'ozon_phone_checked_at' => gmdate('Y-m-d H:i:s'),
            'ozon_phone_available' => (int)$allowed,
        ], ['user_id' => $userId, 'verified_phone' => $phone]);
        if (!$allowed) {
            throw new OzonException('Ozon не может доставить заказ на этот номер. Укажите другой номер.');
        }
        if ($cart === [] || count($cart) > 30) {
            throw new OzonException('Проверьте товары в корзине.');
        }
        $pointId = (is_int($pickupId) || is_string($pickupId)) ? filter_var($pickupId, FILTER_VALIDATE_INT) : false;
        if ($pointId === false || $pointId < 1) {
            throw new OzonException('Выберите пункт выдачи Ozon.');
        }
        $destination = ['pick_up' => ['map_point_id' => $pointId]];
        $shippingDestination = self::pickupAddress($this->delivery->pickupPoint($pointId));

        $items = [];
        $prices = [];
        $orderItems = [];
        $catalogItems = [];
        foreach ($cart as $row) {
            if (!is_array($row)) {
                throw new OzonException('Проверьте товары в корзине.');
            }
            $id = filter_var($row['productId'] ?? $row['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($row['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1 || $quantity === false || $quantity < 1 || $quantity > 100) {
                throw new OzonException('Проверьте количество товаров в корзине.');
            }
            $product = $this->db->fetchAssociative(
                "SELECT p.price, p.legal_status, l.stock, l.fbo_sku
                 FROM products p JOIN ozon_catalog_products l ON l.product_id = p.id AND l.archived = 0
                 WHERE p.id = ? AND p.is_active = 1 AND p.deleted_at IS NULL AND p.availability = 'in_stock'",
                [$id],
            );
            if ($product === false || (int)$product['stock'] < $quantity || (int)$product['fbo_sku'] <= 0) {
                throw new OzonException('Доставка Ozon для этого товара недоступна.');
            }
            $sku = (int) $product['fbo_sku'];
            if ($product['legal_status'] === 'bad' && !$this->payments->markingConfigured()) {
                throw new OzonException('Продажа маркируемого товара через Ozon временно недоступна.');
            }
            if ((int)$product['stock'] < ($items[$sku]['quantity'] ?? 0) + $quantity) {
                throw new OzonException('На складе Ozon недостаточно товара. Проверьте количество в корзине.');
            }
            $items[$sku] = ['sku' => $sku, 'quantity' => ($items[$sku]['quantity'] ?? 0) + $quantity];
            $prices[$sku] = (int) $product['price'];
            $orderItems[$id] = [
                'quantity' => ($orderItems[$id]['quantity'] ?? 0) + $quantity, 'price' => (int)$product['price'],
            ];
            $catalogItems[$id] = ['sku' => $sku, 'marked' => $product['legal_status'] === 'bad'];
        }
        if (count($catalogItems) !== count($items)) {
            throw new OzonException('Проверьте товары в корзине.');
        }
        $items = array_values($items);
        $splits = $this->delivery->quote($phone, $destination, $items);

        return [
            'destination' => $destination,
            'verifiedPhone' => $phone,
            'items' => $items,
            'prices' => $prices,
            'orderItems' => $orderItems,
            'shippingDestination' => $shippingDestination,
            'deliveryCostMinor' => $this->delivery->quotedCostMinor($splits),
            'dates' => OzonDelivery::dates($splits),
            'catalogItems' => $catalogItems,
        ];
    }

    public function fingerprint(array $quote): string
    {
        return hash('sha256', json_encode($quote, JSON_THROW_ON_ERROR));
    }

    public function saveOrder(string $orderId, array $payload): void
    {
        $items = [];
        foreach ($this->db->fetchAllAssociative('SELECT product_id, price, quantity FROM order_items WHERE order_id = ?', [$orderId]) as $row) {
            $items[(int) $row['product_id']] = ['quantity' => (int) $row['quantity'], 'price' => (int) $row['price']];
        }
        if ($items != $payload['orderItems']) {
            throw new OzonException('Состав или цена заказа изменились. Проверьте корзину перед оформлением.');
        }
        unset($payload['orderItems'], $payload['verifiedPhone']);
        $this->db->insert('ozon_delivery_orders', [
            'order_id' => $orderId,
            'status' => 'awaiting_payment',
            'mode' => 'live',
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function status(string $orderId): ?array
    {
        $row = $this->db->fetchAssociative(
            "SELECT status, external_number, postings FROM ozon_delivery_orders WHERE order_id = ? AND mode = 'live'",
            [$orderId],
        );
        return $row === false ? null : [
            'status' => $row['status'],
            'orderNumber' => $row['external_number'],
            'postings' => json_decode($row['postings'] ?? '[]', true, flags: JSON_THROW_ON_ERROR),
        ];
    }

    public function tracking(string $orderId): ?array
    {
        $delivery = $this->status($orderId);
        return ($delivery['orderNumber'] ?? null) !== null ? $delivery['postings'] : null;
    }
}
