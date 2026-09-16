<?php

declare(strict_types=1);

namespace App\Modules\PartnerOffer\Service;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Program\Service\ProgramService;
use Doctrine\DBAL\Connection;

final readonly class PartnerPromoService
{
    public function __construct(private Connection $db, private OfferService $offers, private ProgramService $program) {}

    public function request(int $user, array $payload): array
    {
        $this->offers->assertPartner($user);
        $description = trim((string)($payload['description'] ?? ''));
        $percent = filter_var($payload['percent'] ?? null, FILTER_VALIDATE_INT);
        if ($description === '' || mb_strlen($description) > 2000 || $percent === false || $percent < 1 || $percent > 100) {
            throw new DomainExceptionModule('program', 'Укажите описание и желаемую скидку от 1 до 100%.', 20, status: 422);
        }
        $id = bin2hex(random_bytes(16));
        $now = gmdate('Y-m-d H:i:s');
        $this->db->insert('partner_promo_requests', ['id' => $id, 'user_id' => $user, 'status' => 'pending', 'request' => json_encode(['description' => $description, 'percent' => $percent], JSON_THROW_ON_ERROR), 'rules' => '{}', 'promo_code_id' => null, 'reason' => null, 'processed_by' => null, 'created_at' => $now, 'updated_at' => $now]);
        return ['id' => $id, 'status' => 'pending'];
    }

    public function listing(?int $user, int $page = 1): array
    {
        if ($user !== null) {
            $this->offers->assertPartner($user);
        }
        $rows = $this->db->fetchAllAssociative('SELECT r.*,p.code,p.value,p.is_active,p.used_count FROM partner_promo_requests r LEFT JOIN promo_codes p ON p.id=r.promo_code_id' . ($user !== null ? ' WHERE r.user_id=?' : '') . ' ORDER BY r.created_at DESC LIMIT 25 OFFSET ' . (max(0, $page - 1) * 25), $user !== null ? [$user] : []);
        foreach ($rows as &$row) {
            $row['request'] = json_decode($row['request'], true, 512, JSON_THROW_ON_ERROR);
            $row['rules'] = json_decode($row['rules'], true, 512, JSON_THROW_ON_ERROR);
        }
        return ['items' => $rows, 'page' => max(1, $page), 'limit' => 25];
    }

    public function decide(string $id, int $admin, array $payload): array
    {
        return $this->program->atomic(function () use ($id, $admin, $payload): array {
            $row = $this->db->fetchAssociative('SELECT * FROM partner_promo_requests WHERE id=? FOR UPDATE', [$id]);
            if (!$row || $row['status'] !== 'pending') {
                throw new DomainExceptionModule('program', 'Заявка не найдена или уже обработана.', 21);
            }
            $status = (string)($payload['status'] ?? '');
            $reason = trim((string)($payload['reason'] ?? ''));
            if (!\in_array($status, ['approved', 'rejected'], true) || $reason === '' || mb_strlen($reason) > 2000) {
                throw new DomainExceptionModule('program', 'Укажите решение и причину.', 22, status: 422);
            }
            $data = ['status' => $status, 'reason' => $reason, 'processed_by' => $admin, 'updated_at' => gmdate('Y-m-d H:i:s')];
            if ($status === 'approved') {
                $this->offers->assertPartner((int)$row['user_id']);
                $code = mb_strtoupper(trim((string)($payload['code'] ?? '')));
                $percent = filter_var($payload['percent'] ?? null, FILTER_VALIDATE_INT);
                $limit = filter_var($payload['usageLimit'] ?? null, FILTER_VALIDATE_INT);
                $min = filter_var($payload['minOrderTotal'] ?? 0, FILTER_VALIDATE_INT);
                $expiry = strtotime((string)($payload['expiresAt'] ?? ''));
                if (!preg_match('/^[A-Z0-9_-]{3,40}$/D', $code) || $percent === false || $percent < 1 || $percent > 100 || $limit === false || $limit < 1 || $min === false || $min < 0 || !$expiry || $expiry <= time()) {
                    throw new DomainExceptionModule('program', 'Проверьте код, процент, лимит, сумму и срок.', 23, status: 422);
                }
                if ($this->db->fetchOne('SELECT id FROM promo_codes WHERE code=?', [$code])) {
                    throw new DomainExceptionModule('program', 'Этот промокод уже существует.', 24, status: 422);
                }
                $rules = self::rules((array)($payload['rules'] ?? []));
                $maxDiscount = (int)($this->program->settings()['maxPromoPercent'] ?? 20);
                if ($percent > $maxDiscount) {
                    throw new DomainExceptionModule('program', 'Скидка превышает допустимый предел программы.', 25, status: 422);
                }
                $this->db->insert('promo_codes', ['code' => $code, 'type' => 'percent', 'value' => $percent, 'min_order_total' => $min, 'starts_at' => gmdate('Y-m-d H:i:s'), 'ends_at' => gmdate('Y-m-d H:i:s', $expiry), 'usage_limit' => $limit, 'used_count' => 0, 'is_active' => 1, 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')]);
                $data['promo_code_id'] = (int)$this->db->lastInsertId();
                $data['rules'] = json_encode($rules, JSON_THROW_ON_ERROR);
            }
            $this->db->update('partner_promo_requests', $data, ['id' => $id]);
            return ['id' => $id] + $data;
        });
    }

    public static function rules(array $input): array
    {
        $ids = $input['productIds'] ?? [];
        if (!\is_array($ids) || \count($ids) > 100) {
            throw new DomainExceptionModule('program', 'Некорректный список товаров.', 26, status: 422);
        }
        foreach ($ids as $id) {
            if (!\is_int($id) || $id < 1) {
                throw new DomainExceptionModule('program', 'Некорректный ID товара.', 27, status: 422);
            }
        }
        $budget = $input['budgetMinor'] ?? 0;
        $per = $input['perUserLimit'] ?? 1;
        if (!\is_int($budget) || $budget < 0 || !\is_int($per) || $per < 0 || $per > 1000) {
            throw new DomainExceptionModule('program', 'Некорректный бюджет или лимит покупателя.', 28, status: 422);
        }
        return ['productIds' => array_values(array_unique($ids)), 'budgetMinor' => $budget, 'perUserLimit' => $per, 'firstOrderOnly' => (bool)($input['firstOrderOnly'] ?? false), 'allowBonuses' => (bool)($input['allowBonuses'] ?? false), 'allowSaleProducts' => (bool)($input['allowSaleProducts'] ?? false)];
    }

    /** Called within the program transaction before persisting an order; standard codes remain unchanged. */
    public function validateCheckout(?string $code, array $items, ?int $user, bool $useBonuses, int $discount): void
    {
        if (!$code) {
            return;
        }
        $row = $this->db->fetchAssociative("SELECT r.rules,p.id FROM partner_promo_requests r JOIN promo_codes p ON p.id=r.promo_code_id WHERE p.code=? AND r.status='approved'", [mb_strtoupper(trim($code))]);
        if (!$row) {
            return;
        }
        $rules = self::rules(json_decode($row['rules'], true, 512, JSON_THROW_ON_ERROR));
        if (!$rules['allowBonuses'] && $useBonuses) {
            throw new DomainExceptionModule('program', 'Этот промокод нельзя совмещать со списанием бонусов.', 29, status: 422);
        }
        if (($rules['perUserLimit'] > 0 || $rules['firstOrderOnly']) && $user === null) {
            throw new DomainExceptionModule('program', 'Для этого промокода войдите в личный кабинет.', 30, status: 422);
        }
        if ($rules['firstOrderOnly'] && $this->db->fetchOne("SELECT id FROM orders WHERE user_id=? AND status NOT IN ('cancelled','canceled') LIMIT 1", [$user])) {
            throw new DomainExceptionModule('program', 'Промокод действует только на первую покупку.', 31, status: 422);
        }
        $where = " FROM promo_code_redemptions r JOIN orders o ON BINARY o.id=BINARY r.order_id WHERE r.promo_code_id=? AND o.status NOT IN ('cancelled','canceled')";
        if ($rules['perUserLimit'] > 0 && (int)$this->db->fetchOne('SELECT COUNT(*)' . $where . ' AND o.user_id=?', [$row['id'], $user]) >= $rules['perUserLimit']) {
            throw new DomainExceptionModule('program', 'Лимит использования промокода исчерпан.', 32, status: 422);
        }
        if ($rules['budgetMinor'] > 0 && (int)$this->db->fetchOne('SELECT COALESCE(SUM(o.discount_amount),0)' . $where, [$row['id']]) * 100 + $discount * 100 > $rules['budgetMinor']) {
            throw new DomainExceptionModule('program', 'Бюджет промокода исчерпан.', 33, status: 422);
        }
        foreach ($items as $item) {
            $id = (int)($item['product_id'] ?? $item['productId'] ?? 0);
            if ($rules['productIds'] !== [] && !\in_array($id, $rules['productIds'], true)) {
                throw new DomainExceptionModule('program','Промокод не действует на один из товаров корзины.',34,status: 422);
            }
            if (!$rules['allowSaleProducts'] && $this->db->fetchOne('SELECT id FROM products WHERE id=? AND old_price>price',[$id])) {
                throw new DomainExceptionModule('program','Промокод нельзя применить к акционному товару.',35,status: 422);
            }
        }
    }
}
