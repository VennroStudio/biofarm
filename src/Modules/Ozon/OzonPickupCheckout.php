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

        $stocks = $aliases = [];
        $fbsEnabled = $this->connection->fbsConnected();
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
                "SELECT p.price, p.legal_status, l.stock, l.fbo_sku, l.fbs_sku, l.fbs_stock, l.sku
                 FROM products p JOIN ozon_catalog_products l ON l.product_id = p.id AND l.archived = 0
                 WHERE p.id = ? AND p.is_active = 1 AND p.deleted_at IS NULL AND p.availability = 'in_stock'",
                [$id],
            );
            if ($product === false) {
                throw new OzonException('Доставка Ozon для этого товара недоступна.');
            }
            $skus = ['FBO' => (int)$product['fbo_sku'], 'FBS' => (int)$product['fbs_sku']];
            $qty = ($orderItems[$id]['quantity'] ?? 0) + $quantity;
            $stocks[$id] = ['FBO' => $skus['FBO'] ? (int)$product['stock'] : 0,
                'FBS' => $fbsEnabled && $skus['FBS'] ? (int)$product['fbs_stock'] : 0];
            $holds = $this->db->fetchAllKeyValue('SELECT schema_name, SUM(quantity) FROM ozon_stock_reservations
                WHERE product_id=? AND expires_at > UTC_TIMESTAMP() GROUP BY schema_name', [$id]);
            foreach ($stocks[$id] as $scheme => $count) {
                $stocks[$id][$scheme] = max(0, $count - (int)($holds[$scheme] ?? 0));
            }
            if ($qty > array_sum($stocks[$id])) {
                throw new OzonException('На складах недостаточно товара. Проверьте количество в корзине.');
            }
            if ($product['legal_status'] === 'bad' && !$this->payments->markingConfigured()) {
                throw new OzonException('Продажа маркируемого товара через Ozon временно недоступна.');
            }
            $sku = (int)$product['sku'] ?: ($skus['FBO'] ?: $skus['FBS']);
            foreach (array_filter([$sku, ...array_values($skus)]) as $alias) {
                if (isset($aliases[$alias]) && $aliases[$alias] !== $id) {
                    throw new OzonException('Неоднозначная привязка SKU к карточке.');
                }
                $aliases[$alias] = $id;
                $prices[$alias] = (int)$product['price'];
            }
            $orderItems[$id] = ['quantity' => $qty, 'price' => (int)$product['price']];
            $catalogItems[$id] = ['sku' => $sku, 'skus' => $skus, 'marked' => $product['legal_status'] === 'bad'];
        }
        $matched = false;
        foreach ($fbsEnabled ? ['FBO', 'FBS', 'MIX'] : ['FBO'] as $schema) {
            $quoteItems = [];
            foreach ($orderItems as $id => $item) {
                if ($schema !== 'MIX' && $stocks[$id][$schema] < $item['quantity']) {
                    continue 2;
                }
                $sku = $schema === 'MIX' ? $catalogItems[$id]['sku'] : $catalogItems[$id]['skus'][$schema];
                $quoteItems[] = ['sku' => $sku, 'quantity' => $item['quantity']];
            }
            try {
                $splits = $this->delivery->quote($phone, $destination, $quoteItems, $schema, $aliases);
            } catch (OzonException $e) {
                if (!$e->quoteUnavailable) {
                    throw $e;
                }
                continue;
            }
            $plan = $this->delivery->shipmentPlan($splits);
            $allocation = [];
            foreach ($plan as $part) {
                foreach ($part['items'] as $sku => $quantity) {
                    $id = $aliases[$sku];
                    $scheme = $part['schema'];
                    $allocation[$id][$scheme] = ($allocation[$id][$scheme] ?? 0) + $quantity;
                    if ($allocation[$id][$scheme] > $stocks[$id][$scheme]) {
                        continue 3;
                    }
                }
            }
            $matched = true;
            break;
        }
        if (!$matched) {
            throw new OzonException('Ozon не подтвердил доставку всей корзины в этот пункт.');
        }
        $items = [];
        foreach ($plan as $part) {
            foreach ($part['items'] as $sku => $quantity) {
                $items[] = ['sku' => $sku, 'quantity' => $quantity];
            }
        }
        return [
            'destination' => $destination,
            'verifiedPhone' => $phone,
            'schema' => $schema, 'shipments' => $plan, 'allocation' => $allocation,
            'quoteItems' => $quoteItems, 'aliases' => $aliases,
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
        $allocation = $payload['allocation'];
        ksort($allocation, SORT_NUMERIC);
        $expiresAt = gmdate('Y-m-d H:i:s', time() + 1800);
        foreach ($allocation as $id => $amounts) {
            $stock = $this->db->fetchAssociative('SELECT stock AS FBO, fbs_stock AS FBS FROM ozon_catalog_products WHERE product_id=? FOR UPDATE', [$id]);
            $holds = $this->db->fetchAllAssociative('SELECT schema_name, quantity FROM ozon_stock_reservations
                WHERE product_id=? AND expires_at > UTC_TIMESTAMP() FOR UPDATE', [$id]);
            foreach ($holds as $hold) {
                $stock[$hold['schema_name']] -= (int)$hold['quantity'];
            }
            foreach ($amounts as $scheme => $quantity) {
                if ($quantity > (int)($stock[$scheme] ?? 0)) {
                    throw new OzonException('Товар уже зарезервирован. Обновите корзину.');
                }
                $this->db->insert('ozon_stock_reservations', ['order_id' => $orderId, 'product_id' => $id,
                    'schema_name' => $scheme, 'quantity' => $quantity, 'expires_at' => $expiresAt]);
            }
        }
        $payload['placedAt'] = gmdate(DATE_ATOM);
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
