<?php

declare(strict_types=1);

namespace App\Modules\PartnerOffer\Service;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Setting\SiteSettings;
use Doctrine\DBAL\Connection;

final readonly class OfferService
{
    public function __construct(private Connection $db, private SiteSettings $settings) {}

    public function assertPartner(int $user): void
    {
        if (!$this->settings->bool('referral_enabled') || !(bool)$this->db->fetchOne('SELECT is_partner FROM user_profiles WHERE user_id=?', [$user])) {
            throw new DomainExceptionModule('program', 'Инструмент доступен партнёрам программы.', 1, status: 403);
        }
    }

    public function create(int $user, array $payload): array
    {
        $this->assertPartner($user);
        $title = trim((string)($payload['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 150) {
            throw new DomainExceptionModule('program', 'Укажите название до 150 символов.', 2, status: 422);
        }
        $input = $payload['items'] ?? [];
        if (!\is_array($input) || \count($input) < 1 || \count($input) > 50) {
            throw new DomainExceptionModule('program', 'Добавьте от 1 до 50 товаров.', 3, status: 422);
        }
        $items = [];
        foreach ($input as $item) {
            $id = filter_var($item['productId'] ?? null, FILTER_VALIDATE_INT);
            $qty = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || !$qty || $qty > 99 || $qty < 1 || isset($items[$id])) {
                throw new DomainExceptionModule('program', 'Некорректный товар или количество.', 4, status: 422);
            }
            if (!$this->db->fetchOne("SELECT id FROM products WHERE id=? AND is_active=1 AND deleted_at IS NULL AND availability<>'out_of_stock'", [$id])) {
                throw new DomainExceptionModule('program', 'Один из товаров недоступен.', 5, status: 422);
            }
            $items[$id] = ['productId' => $id, 'quantity' => $qty];
        }
        $expiry = trim((string)($payload['expiresAt'] ?? ''));
        $expires = $expiry !== '' ? strtotime($expiry) : null;
        if ($expires !== null && ($expires === false || $expires <= time())) {
            throw new DomainExceptionModule('program', 'Срок предложения должен быть в будущем.', 6, status: 422);
        }
        $id = bin2hex(random_bytes(24));
        $this->db->insert('partner_offers', ['id' => $id, 'user_id' => $user, 'title' => $title, 'items' => json_encode(array_values($items), JSON_THROW_ON_ERROR), 'promo_code' => null, 'expires_at' => $expires ? gmdate('Y-m-d H:i:s', $expires) : null, 'is_active' => 1, 'visits' => 0, 'created_at' => gmdate('Y-m-d H:i:s')]);
        return ['id' => $id, 'url' => '/checkout?offer=' . $id];
    }

    public function listing(int $user, int $page = 1): array
    {
        $this->assertPartner($user);
        $rows = $this->db->fetchAllAssociative("SELECT o.*, (SELECT COUNT(*) FROM partner_offer_orders po WHERE po.offer_id=o.id) AS orders_count,(SELECT COUNT(*) FROM partner_offer_orders po JOIN orders x ON BINARY x.id=BINARY po.order_id WHERE po.offer_id=o.id AND x.payment_status='completed') AS paid_count FROM partner_offers o WHERE user_id=? ORDER BY created_at DESC LIMIT 25 OFFSET " . (max(0, $page - 1) * 25), [$user]);
        foreach ($rows as &$row) {
            unset($row['promo_code']);
            $row['items'] = json_decode($row['items'], true, 512, JSON_THROW_ON_ERROR);
            $row['url'] = '/checkout?offer=' . $row['id'];
        }
        return ['items' => $rows, 'page' => max(1, $page), 'limit' => 25];
    }

    public function disable(int $user, string $id): void
    {
        $this->assertPartner($user);
        $this->db->update('partner_offers', ['is_active' => 0], ['id' => $id, 'user_id' => $user]);
    }

    /** Owner-only history remains available after a basket expires or is disabled. */
    public function details(int $user, string $id, int $page = 1): array
    {
        $this->assertPartner($user);
        $offer = $this->db->fetchAssociative('SELECT id,title,items,is_active,expires_at,created_at FROM partner_offers WHERE id=? AND user_id=?', [$id, $user]);
        if (!$offer) {
            throw new DomainExceptionModule('program', 'Корзина не найдена.', 11, status: 404);
        }
        $items = [];
        $total = 0;
        $completeTotal = true;
        foreach (json_decode($offer['items'], true, 512, JSON_THROW_ON_ERROR) as $item) {
            $product = $this->db->fetchAssociative('SELECT name,price,image,is_active,availability,deleted_at FROM products WHERE id=?', [$item['productId']]);
            $quantity = (int)$item['quantity'];
            $price = $product ? (int)$product['price'] : null;
            $lineTotal = $price !== null ? $price * $quantity : null;
            $total += $lineTotal ?? 0;
            $completeTotal = $completeTotal && $price !== null;
            $items[] = [
                'productId' => (int)$item['productId'], 'name' => $product['name'] ?? 'Удалённый товар #' . $item['productId'],
                'image' => $product['image'] ?? null, 'quantity' => $quantity, 'price' => $price, 'total' => $lineTotal,
                'available' => $product && (bool)$product['is_active'] && $product['deleted_at'] === null && $product['availability'] !== 'out_of_stock',
            ];
        }
        $count = (int)$this->db->fetchOne('SELECT COUNT(*) FROM partner_offer_orders po JOIN orders o ON BINARY o.id=BINARY po.order_id WHERE po.offer_id=?', [$id]);
        $pages = max(1, (int)ceil($count / 25));
        $page = max(1, min($page, $pages));
        // Select only the fields needed by the partner; never expose payment tokens or delivery contacts.
        $orders = $this->db->fetchAllAssociative('SELECT o.id,o.status,o.payment_status,o.total,o.created_at,JSON_UNQUOTE(JSON_EXTRACT(o.shipping_address, "$.name")) AS customer_name FROM partner_offer_orders po JOIN orders o ON BINARY o.id=BINARY po.order_id WHERE po.offer_id=? ORDER BY o.created_at DESC,o.id DESC LIMIT 25 OFFSET ' . (($page - 1) * 25), [$id]);
        foreach ($orders as &$order) {
            $order['total'] = (int)$order['total'];
            $order['offerId'] = $id;
        }
        unset($order);
        return [
            'id' => $offer['id'], 'title' => $offer['title'], 'isActive' => (bool)$offer['is_active'],
            'isExpired' => $offer['expires_at'] !== null && strtotime($offer['expires_at'] . ' UTC') <= time(),
            'expiresAt' => $offer['expires_at'], 'createdAt' => $offer['created_at'],
            'items' => $items, 'total' => $completeTotal ? $total : null,
            'orders' => ['items' => $orders, 'total' => $count, 'page' => $page, 'pages' => $pages],
        ];
    }

    public function read(string $id, bool $visit = true): array
    {
        if (!$this->settings->bool('cart_enabled') || !preg_match('/^[a-f0-9]{48}$/D', $id)) {
            throw new DomainExceptionModule('program', 'Предложение недоступно.', 8, status: 404);
        }
        $row = $this->db->fetchAssociative('SELECT o.*,p.referral_code FROM partner_offers o JOIN user_profiles p ON p.user_id=o.user_id JOIN users u ON u.id=p.user_id WHERE o.id=? AND o.is_active=1 AND p.is_partner=1 AND u.deleted_at IS NULL AND (o.expires_at IS NULL OR o.expires_at>UTC_TIMESTAMP())', [$id]);
        if (!$row) {
            throw new DomainExceptionModule('program', 'Предложение отключено или срок истёк.', 9, status: 404);
        }
        $items = [];
        foreach (json_decode($row['items'], true, 512, JSON_THROW_ON_ERROR) as $item) {
            $product = $this->db->fetchAssociative('SELECT id,slug,name,price,image,weight,is_active,availability,deleted_at FROM products WHERE id=?', [$item['productId']]);
            if (!$product || !(bool)$product['is_active'] || $product['deleted_at'] !== null || $product['availability'] === 'out_of_stock') {
                throw new DomainExceptionModule('program', 'Товар из предложения сейчас недоступен. Обратитесь к партнёру.', 10, status: 409);
            }
            $items[] = ['product' => ['id' => (int)$product['id'], 'slug' => $product['slug'], 'name' => $product['name'], 'price' => (int)$product['price'], 'image' => $product['image'], 'weight' => $product['weight']], 'quantity' => (int)$item['quantity']];
        }
        if ($visit) {
            $this->db->executeStatement('UPDATE partner_offers SET visits=visits+1 WHERE id=?', [$id]);
        }
        return ['id' => $id, 'title' => $row['title'], 'items' => $items, 'referralCode' => $row['referral_code'], 'expiresAt' => $row['expires_at']];
    }

    public function recordOrder(string $id,string $order): void
    {
        $this->db->insert('partner_offer_orders',['order_id' => $order, 'offer_id' => $id]);
    }
}
