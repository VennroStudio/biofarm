<?php

declare(strict_types=1);

namespace App\Modules\Program\Service;

use App\Components\Clock\UtcClock;
use App\Components\Setting\SiteSettings;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use DomainException;

/** All monetary transitions serialize on the program mutex, including nested caller transactions. */
final class ProgramService
{
    public function __construct(private Connection $db, private ?Closure $clock = null) {}

    public function atomic(callable $callback): mixed
    {
        return $this->db->transactional(function () use ($callback) {
            $this->db->executeStatement($this->db->getDatabasePlatform() instanceof SQLitePlatform
                ? "INSERT OR IGNORE INTO program_locks (id,payload) VALUES ('finance','{}')"
                : "INSERT INTO program_locks (id,payload) VALUES ('finance','{}') ON DUPLICATE KEY UPDATE id=VALUES(id)");
            $this->db->fetchOne($this->lockSql("SELECT id FROM program_locks WHERE id='finance'"));
            $releaseUsers = $this->db->fetchFirstColumn("SELECT DISTINCT user_id FROM program_ledger WHERE wallet='shopping' AND state IN ('pending','refund_pending') AND available_at IS NOT NULL AND available_at<=?", [$this->now()]);
            $this->db->executeStatement("UPDATE program_ledger SET state='available' WHERE state='pending' AND available_at IS NOT NULL AND available_at<=?", [$this->now()]);
            $this->db->executeStatement("UPDATE program_ledger SET state='reserved' WHERE state='refund_pending' AND available_at IS NOT NULL AND available_at<=?", [$this->now()]);
            foreach ($releaseUsers as $userId) {
                $this->syncProfile((int)$userId);
            }
            return $callback();
        });
    }

    public function identity(int $user): array
    {
        return new ProgramParticipants($this->db)->identity($user);
    }

    public function teamInvite(int $user): array
    {
        return $this->atomic(fn () => new ProgramParticipants($this->db)->invite($user));
    }

    public function teamInvitation(string $code): array
    {
        return new ProgramParticipants($this->db)->invitation($code);
    }

    public function joinTeam(int $user, string $code, bool $consent): array
    {
        if (!new SiteSettings($this->db)->bool('referral_enabled')) {
            throw new DomainException('Программа отключена');
        }
        return $this->atomic(fn () => new ProgramParticipants($this->db)->join($user, $code, $consent));
    }

    public function settings(): array
    {
        $value = $this->db->fetchOne("SELECT payload FROM program_locks WHERE id='settings'");
        return ProgramMath::rules([], $value === false ? [] : $this->decode($value));
    }

    public function updateSettings(array $input, int $actor): array
    {
        return $this->atomic(function () use ($input, $actor) {
            $rules = ProgramMath::rules($input, $this->settings());
            $this->db->insert('program_rules', ['id' => bin2hex(random_bytes(16)), 'payload' => $this->json($rules), 'created_at' => $this->now()]);
            $this->db->delete('program_locks', ['id' => 'settings']);
            $this->db->insert('program_locks', ['id' => 'settings', 'payload' => $this->json($rules)]);
            $this->audit($actor, 'settings', $rules);
            return $rules;
        });
    }

    public function shoppingAvailable(int $user): int
    {
        return $this->atomic(function () use ($user) {
            $this->opening($user);
            return max(0, $this->balance($user, 'shopping')['availableMinor']);
        });
    }

    public function captureOrder(string $orderId): void
    {
        $this->atomic(function () use ($orderId): void {
            if ($this->db->fetchOne('SELECT id FROM program_orders WHERE id=?', [$orderId]) !== false) {
                return;
            }
            $o = $this->db->fetchAssociative($this->lockSql('SELECT * FROM orders WHERE id=?'), [$orderId]);
            if (!$o) {
                throw new DomainException('Order not found');
            }
            $rules = $this->settings();
            $buyer = $o['user_id'] === null ? null : (int)$o['user_id'];
            $profile = $buyer === null ? null : $this->db->fetchAssociative('SELECT * FROM user_profiles WHERE user_id=?', [$buyer]);
            $flags = new SiteSettings($this->db);
            $parent = null;
            $provisional = false;
            if ($flags->bool('referral_enabled') && empty($profile['is_partner'])) {
                $parent = $profile['referred_by_user_id'] ?? null;
                if ($parent === null && !empty($o['referred_by'])) {
                    $parent = $this->referralOwner((string)$o['referred_by']);
                    $parent = $this->safeParent($buyer, $parent);
                    $provisional = $buyer !== null && $parent !== null;
                }
            }
            $address = json_decode($o['shipping_address'] ?? '{}', true) ?: [];
            $email = $buyer === null ? mb_strtolower(trim((string)($address['email'] ?? ''))) : '';
            $recipients = $this->recipients($buyer, $parent === null ? null : (int)$parent, $rules, $flags->bool('order_bonus_enabled'), $email);
            if ($buyer !== null) {
                $this->opening($buyer);
            }
            $rows = $this->db->fetchAllAssociative('SELECT * FROM order_items WHERE order_id=? ORDER BY id', [$orderId]);
            $weights = [];
            foreach ($rows as $r) {
                $weights[$r['id']] = (int)$r['price'] * (int)$r['quantity'] * 100;
            }
            $gross = array_sum($weights);
            $discount = (int)$o['discount_amount'] * 100;
            $spent = (int)$o['bonus_used'] * 100;
            if ($spent > $gross - $discount) {
                throw new DomainException('Shopping bonuses cannot cover delivery');
            }
            $discounts = ProgramMath::allocate($discount, $weights);
            $net = [];
            foreach ($weights as $id => $w) {
                $net[$id] = $w - $discounts[$id];
            }
            $spending = ProgramMath::allocate($spent, $net);
            $items = [];
            foreach ($rows as $r) {
                $id = (int)$r['id'];
                $paid = $net[$id] - $spending[$id];
                $rewards = [];
                foreach ($recipients as $recipient) {
                    $reward = $recipient;
                    $reward['amountMinor'] = intdiv($paid * $recipient['bps'], 10000);
                    $rewards[] = $reward;
                }
                $items[] = ['itemId' => $id, 'productId' => (int)$r['product_id'], 'description' => $r['product_name'], 'quantity' => (int)$r['quantity'], 'paidMinor' => $paid, 'spentMinor' => $spending[$id], 'discountMinor' => $discounts[$id], 'refundedQuantity' => 0, 'rewards' => $rewards];
            }
            if ($spent > 0) {
                if ($buyer === null || $this->balance($buyer, 'shopping')['availableMinor'] < $spent) {
                    throw new DomainException('Insufficient shopping bonus balance');
                }
                $this->entry('spend:' . $orderId, $buyer, 'shopping', -$spent, 'order_spending', $orderId, 'reserved');
                $this->syncProfile($buyer);
            }
            $snapshot = ['modelVersion' => 2, 'provisionalReferral' => $provisional, 'referralParentId' => $parent, 'items' => $items, 'rules' => $rules, 'totalMinor' => (int)$o['total'] * 100, 'deliveryMinor' => (int)$o['delivery_cost'] * 100, 'deliveryRefunded' => false, 'bonusMinor' => $spent, 'recipients' => $recipients];
            $this->db->insert('program_orders', ['id' => $orderId, 'buyer_id' => $buyer, 'status' => 'pending', 'snapshot' => $this->json($snapshot), 'delivered_at' => null]);
        });
    }

    public function referralOwner(string $code): ?int
    {
        $id = $this->db->fetchOne('SELECT p.user_id FROM user_profiles p JOIN users u ON u.id=p.user_id WHERE (p.referral_code=? OR p.user_id=?) AND u.deleted_at IS NULL AND u.status=1 LIMIT 1', [$code, ctype_digit($code) ? (int)$code : 0]);
        return $id === false ? null : (int)$id;
    }

    public function settleOrder(string $orderId): void
    {
        $this->atomic(function () use ($orderId): void {
            $o = $this->order($orderId);
            if (!$o || $o['status'] === 'paid') {
                return;
            }if ($o['status'] !== 'pending') {
                throw new DomainException('Cancelled order cannot be settled');
            }
            if ($this->db->fetchOne('SELECT payment_status FROM orders WHERE id=?', [$orderId]) !== 'completed') {
                throw new DomainException('Payment is not confirmed');
            }
            $s = $this->decode($o['snapshot']);
            // Only a previously unbound buyer may be attached by checkout. The first
            // confirmed payment wins; pending orders never replace an existing team.
            if (($s['modelVersion'] ?? 1) >= 2 && ($s['provisionalReferral'] ?? false) && $o['buyer_id'] !== null) {
                $buyer = (int)$o['buyer_id'];
                $profile = $this->db->fetchAssociative('SELECT * FROM user_profiles WHERE user_id=?', [$buyer]);
                $canBind = !$this->identity($buyer)['canEarnCommission'];
                $parent = $canBind ? ($profile['referred_by_user_id'] ?? null) : null;
                if ($canBind && $parent === null) {
                    $candidate = $this->referralOwner((string)$s['referralParentId']);
                    $parent = $this->safeParent($buyer, $candidate);
                    if ($parent !== null) {
                        $this->db->update('user_profiles', ['referred_by_user_id' => $parent], ['user_id' => $buyer]);
                        $this->audit($buyer, 'referral_attached', ['userId' => $buyer, 'parentId' => $parent, 'orderId' => $orderId]);
                    }
                }
                // Two pending baskets can name different partners. Once a payment
                // establishes the team, later payments use that team at captured rates.
                if ($parent !== $s['referralParentId']) {
                    $buyerBonus = \in_array('buyer', array_column($s['recipients'], 'kind'), true);
                    $s['recipients'] = $this->recipients($buyer, $parent === null ? null : (int)$parent, $s['rules'], $buyerBonus);
                    foreach ($s['items'] as &$item) {
                        $item['rewards'] = [];
                        foreach ($s['recipients'] as $recipient) {
                            $recipient['amountMinor'] = intdiv($item['paidMinor'] * $recipient['bps'], 10000);
                            $item['rewards'][] = $recipient;
                        }
                    }
                    unset($item);
                }
                $s['referralParentId'] = $parent;
                $s['provisionalReferral'] = false;
                $this->db->update('program_orders', ['snapshot' => $this->json($s)], ['id' => $orderId]);
            }
            $at = $o['delivered_at'] === null ? null : new DateTimeImmutable($o['delivered_at'])->modify('+' . $s['rules']['holdDays'] . ' days')->format('Y-m-d H:i:s');
            $this->db->update('program_ledger', ['state' => 'available'], ['id' => 'spend:' . $orderId]);
            foreach ($s['items'] as $item) {
                foreach ($item['rewards'] as $r) {
                    $this->opening($r['userId']);
                    $this->entry('reward:' . $item['itemId'] . ':' . $r['kind'], $r['userId'], $r['wallet'], $r['amountMinor'], $r['kind'], $orderId, 'pending', ['itemId' => $item['itemId'], 'productName' => $item['description'], 'basisMinor' => $item['paidMinor'], 'rateBps' => $r['bps']], $at);
                }
            }
            $this->db->update('program_orders', ['status' => 'paid'], ['id' => $orderId]);
            if ($o['buyer_id'] !== null) {
                $this->syncProfile((int)$o['buyer_id']);
            }
        });
    }

    public function cancelOrder(string $orderId): void
    {
        $this->atomic(function () use ($orderId): void {
            $o = $this->order($orderId);
            if (!$o || $o['status'] === 'cancelled') {
                return;
            }if ($o['status'] === 'paid') {
                throw new DomainException('Paid orders require confirmed refund');
            }
            if ($this->db->fetchOne("SELECT id FROM payment_operations WHERE order_id=? AND kind='payment' AND status<>'canceled' LIMIT 1", [$orderId]) !== false) {
                throw new DomainException('Сначала дождитесь подтверждения отмены платежа в ЮKassa и обновите его статус.');
            }
            $this->db->update('program_ledger', ['state' => 'void'], ['id' => 'spend:' . $orderId]);
            $this->db->update('program_orders', ['status' => 'cancelled'], ['id' => $orderId]);
            $promo = $this->db->fetchOne('SELECT promo_code_id FROM promo_code_redemptions WHERE order_id=?', [$orderId]);
            if ($promo !== false) {
                $this->db->executeStatement('UPDATE promo_codes SET used_count=CASE WHEN used_count>0 THEN used_count-1 ELSE 0 END WHERE id=?', [$promo]);
            }

            if ($o['buyer_id'] !== null) {
                $this->syncProfile((int)$o['buyer_id']);
            }
        });
    }

    public function deliverOrder(string $orderId): void
    {
        $this->atomic(function () use ($orderId): void {
            $o = $this->order($orderId);
            if (!$o || $o['delivered_at'] !== null) {
                return;
            }if ($o['status'] !== 'paid') {
                throw new DomainException('Only paid orders can be delivered');
            }
            $s = $this->decode($o['snapshot']);
            $now = $this->now();
            $at = new DateTimeImmutable($now)->modify('+' . $s['rules']['holdDays'] . ' days')->format('Y-m-d H:i:s');
            $this->db->update('program_orders', ['delivered_at' => $now], ['id' => $orderId]);
            $this->db->update('orders', ['status' => 'delivered', 'updated_at' => $now], ['id' => $orderId]);
            $this->db->executeStatement("UPDATE program_ledger SET available_at=? WHERE order_id=? AND state IN ('pending','refund_pending')", [$at, $orderId]);
        });
    }

    public function reserveRefund(string $orderId, string $refundId, array $items, bool $refundDelivery = false): array
    {
        return $this->atomic(function () use ($orderId, $refundId, $items, $refundDelivery) {
            $existing = $this->db->fetchAssociative('SELECT * FROM program_refunds WHERE id=?', [$refundId]);
            if ($existing) {
                $p = $this->decode($existing['payload']);
                if ($existing['order_id'] !== $orderId || $p['request'] !== $items || $p['refundDelivery'] !== $refundDelivery) {
                    throw new DomainException('Refund key reused with different request');
                }return $p;
            }
            $o = $this->order($orderId);
            if (!$o || $o['status'] !== 'paid') {
                throw new DomainException('Refund requires paid program order');
            }
            if ($this->db->fetchOne("SELECT id FROM program_refunds WHERE order_id=? AND status='pending'", [$orderId]) !== false) {
                throw new DomainException('Another refund is pending');
            }
            $s = $this->decode($o['snapshot']);
            $requested = [];
            foreach ($items as $r) {
                $id = $r['itemId'] ?? 0;
                $q = $r['quantity'] ?? 0;
                if (!\is_int($id) || !\is_int($q) || $q < 1 || isset($requested[$id])) {
                    throw new DomainException('Invalid refund items');
                }
                $requested[$id] = $q;
            }
            $payload = ['amountMinor' => 0, 'items' => [], 'reversals' => [], 'restoreMinor' => 0, 'request' => $items, 'refundDelivery' => $refundDelivery];
            foreach ($s['items'] as $item) {
                $id = $item['itemId'];
                if (!isset($requested[$id])) {
                    continue;
                }
                $q = $requested[$id];
                unset($requested[$id]);
                $prev = $item['refundedQuantity'];
                $total = $item['quantity'];
                $paid = ProgramMath::portion($item['paidMinor'], $prev, $q, $total);
                $payload['amountMinor'] += $paid;
                $payload['restoreMinor'] += ProgramMath::portion($item['spentMinor'], $prev, $q, $total);
                $payload['items'][] = ['itemId' => $id, 'quantity' => $q, 'description' => $item['description'], 'amountMinor' => $paid];
                foreach ($item['rewards'] as $r) {
                    $amount = ProgramMath::portion($r['amountMinor'], $prev, $q, $total);
                    $rewardId = 'reward:' . $id . ':' . $r['kind'];
                    $original = $this->db->fetchAssociative('SELECT state,available_at FROM program_ledger WHERE id=?', [$rewardId]);
                    $key = 'refund:' . $refundId . ':' . $id . ':' . $r['kind'];
                    $payload['reversals'][] = ['id' => $key, 'rewardId' => $rewardId, 'userId' => $r['userId'], 'wallet' => $r['wallet'], 'amountMinor' => $amount];
                    $this->entry($key, $r['userId'], $r['wallet'], -$amount, 'refund_reserve', $orderId, $original['state'] === 'pending' ? 'refund_pending' : 'reserved', [], $original['available_at']);
                }
            }
            if ($requested !== [] || ($payload['items'] === [] && !$refundDelivery)) {
                throw new DomainException('Unknown or missing refund positions');
            }
            if ($refundDelivery) {
                if ($s['deliveryRefunded']) {
                    throw new DomainException('Delivery already refunded');
                }
                $payload['amountMinor'] += $s['deliveryMinor'];
                $payload['items'][] = ['itemId' => null, 'quantity' => 1, 'description' => 'Доставка', 'amountMinor' => $s['deliveryMinor']];
            }
            $this->db->insert('program_refunds', ['id' => $refundId, 'order_id' => $orderId, 'status' => 'pending', 'payload' => $this->json($payload)]);
            return $payload;
        });
    }

    public function completeRefund(string $refundId): void
    {
        $this->atomic(function () use ($refundId): void {
            $r = $this->db->fetchAssociative($this->lockSql('SELECT * FROM program_refunds WHERE id=?'), [$refundId]);
            if (!$r) {
                throw new DomainException('Refund not found');
            }if ($r['status'] === 'completed') {
                return;
            }if ($r['status'] !== 'pending') {
                throw new DomainException('Refund not pending');
            }
            $p = $this->decode($r['payload']);
            $o = $this->order($r['order_id']);
            $s = $this->decode($o['snapshot']);
            foreach ($p['reversals'] as $v) {
                $original = $this->db->fetchAssociative('SELECT state,available_at FROM program_ledger WHERE id=?', [$v['rewardId']]);
                $this->db->update('program_ledger', ['state' => $original['state'], 'available_at' => $original['available_at'], 'kind' => 'refund'], ['id' => $v['id']]);
            }
            foreach ($p['request'] as $v) {
                foreach ($s['items'] as &$item) {
                    if ($item['itemId'] === $v['itemId']) {
                        $item['refundedQuantity'] += $v['quantity'];
                    }
                }
                unset($item);
            }
            if ($p['refundDelivery']) {
                $s['deliveryRefunded'] = true;
            }
            if ($o['buyer_id'] !== null && $p['restoreMinor'] > 0) {
                $this->entry('restore:' . $refundId, (int)$o['buyer_id'], 'shopping', $p['restoreMinor'], 'refund_restore', $o['id']);
                $this->syncProfile((int)$o['buyer_id']);
            }
            $fullyRefunded = ($s['deliveryMinor'] === 0 || $s['deliveryRefunded'])
                && array_all($s['items'], static fn (array $item): bool => $item['refundedQuantity'] === $item['quantity']);
            if ($fullyRefunded) {
                $s['fullyRefunded'] = true;
                $this->db->update('orders', ['payment_status' => 'refunded', 'updated_at' => $this->now()], ['id' => $o['id']]);
            }
            $this->db->update('program_orders', ['snapshot' => $this->json($s)], ['id' => $o['id']]);
            $this->db->update('program_refunds', ['status' => 'completed'], ['id' => $refundId]);
        });
    }

    public function cancelRefund(string $refundId): void
    {
        $this->atomic(function () use ($refundId): void {
            $r = $this->db->fetchAssociative($this->lockSql('SELECT * FROM program_refunds WHERE id=?'), [$refundId]);
            if (!$r || $r['status'] === 'cancelled') {
                return;
            }if ($r['status'] !== 'pending') {
                throw new DomainException('Completed refund cannot be cancelled');
            }
            $p = $this->decode($r['payload']);
            foreach ($p['reversals'] as $v) {
                $this->db->update('program_ledger', ['state' => 'void'], ['id' => $v['id']]);
            }
            $this->db->update('program_refunds', ['status' => 'cancelled'], ['id' => $refundId]);
        });
    }

    public function requestWithdrawal(int $user, mixed $amount, array $details): array
    {
        new ProgramParticipants($this->db)->requireCommissionAccess($user);
        if (!new SiteSettings($this->db)->bool('withdrawals_enabled')) {
            throw new DomainException('Заявки на выплаты временно отключены.');
        }
        $minor = ProgramMath::minor($amount);
        return $this->atomic(function () use ($user, $minor, $details) {
            new ProgramParticipants($this->db)->requireCommissionAccess($user);
            $this->opening($user);
            if ($minor < $this->settings()['minimumWithdrawalMinor'] || $minor > $this->balance($user, 'commission')['availableMinor']) {
                throw new DomainException('Withdrawal amount outside available limits');
            }if ($details === []) {
                throw new DomainException('Recipient details required');
            }
            $id = bin2hex(random_bytes(16));
            $this->db->insert('program_withdrawals', ['id' => $id, 'user_id' => $user, 'amount_minor' => $minor, 'status' => 'pending', 'reference' => null, 'details' => $this->json($details), 'created_at' => $this->now()]);
            $this->entry('payout:' . $id, $user, 'commission', -$minor, 'payout', null, 'reserved');
            return ['id' => $id, 'amountMinor' => $minor, 'status' => 'pending'];
        });
    }

    public function updateWithdrawal(string $id, string $status, ?string $reference, ?string $reason, int $actor): array
    {
        return $this->atomic(function () use ($id, $status, $reference, $reason, $actor) {
            $r = $this->db->fetchAssociative($this->lockSql('SELECT * FROM program_withdrawals WHERE id=?'), [$id]);
            if (!$r) {
                throw new DomainException('Withdrawal not found');
            }if ($r['status'] === $status) {
                if ($status === 'paid' && $r['reference'] !== $reference) {
                    throw new DomainException('Payment reference mismatch');
                }return $r;
            }
            if (!\in_array($status, match ($r['status']) {
                'pending' => ['approved', 'rejected'],'approved' => ['paid', 'rejected'],default => []
            }, true)) {
                throw new DomainException('Invalid withdrawal transition');
            }
            if ($status === 'paid' && trim((string)$reference) === '') {
                throw new DomainException('Payment reference required');
            }
            if ($status === 'rejected' && trim((string)$reason) === '') {
                throw new DomainException('Rejection reason required');
            }
            if ($status === 'paid' && $this->balance((int)$r['user_id'], 'commission')['availableMinor'] < 0) {
                throw new DomainException('Refund debt prevents payout');
            }
            if ($status === 'paid' && $this->db->fetchOne("SELECT id FROM program_withdrawals WHERE reference=? AND status='paid'", [$reference]) !== false) {
                throw new DomainException('Payment reference already used');
            }
            $details = $this->decode($r['details']);
            $details['decisionReason'] = $reason;
            $this->db->update('program_withdrawals', ['status' => $status, 'reference' => $reference, 'details' => $this->json($details)], ['id' => $id]);
            if ($status === 'paid' || $status === 'rejected') {
                $this->db->update('program_ledger', ['state' => $status === 'paid' ? 'available' : 'void'], ['id' => 'payout:' . $id]);
            }
            $this->audit($actor, 'withdrawal', ['id' => $id, 'status' => $status, 'reference' => $reference, 'reason' => $reason]);
            return array_replace($r, ['status' => $status, 'reference' => $reference]);
        });
    }

    public function dashboard(int $user): array
    {
        return $this->atomic(function () use ($user) {
            $this->opening($user);
            $this->syncProfile($user);
            return ['identity' => $this->identity($user), 'balances' => ['shopping' => $this->balance($user, 'shopping'), 'commission' => $this->balance($user, 'commission')], 'rates' => $this->settings(), 'rateUnit' => 'basis_points', 'currency' => 'RUB', 'moneyUnit' => 'minor'];
        });
    }

    public function listing(string $kind, ?int $user, int $page = 1, int $limit = 25, string $sort = 'depth', string $direction = 'asc', ?string $wallet = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return $this->atomic(function () use ($kind, $user, $page, $limit, $sort, $direction, $wallet, $dateFrom, $dateTo) {
            $limit = max(1, min(100, $limit));
            $offset = (max(1, $page) - 1) * $limit;
            $params = [];
            $orderBy = '1 DESC';
            if ($user !== null && ($kind !== 'ledger' || $wallet !== 'shopping')) {
                if (\in_array($kind, ['ledger', 'withdrawals'], true)) {
                    new ProgramParticipants($this->db)->requireCommissionAccess($user);
                } else {
                    new ProgramParticipants($this->db)->requireParticipant($user, $kind === 'team');
                }
            }
            if ($kind === 'team' || $kind === 'referrals') {
                if ($user === null) {
                    throw new DomainException('Укажите владельца списка');
                }
                $sortColumn = match ($sort) {
                    'name'  => 'name', 'created_at' => 'u.created_at', 'depth' => 'u.id',
                    default => throw new DomainException('Unknown team sort column'),
                };
                if (!\in_array($direction, ['asc', 'desc'], true)) {
                    throw new DomainException('Unknown team sort direction');
                }
                $orderBy = $sortColumn . ' ' . strtoupper($direction) . ', u.id ASC';
                $sql = "SELECT u.id,u.first_name,u.last_name,u.created_at,TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) name FROM users u JOIN user_profiles p ON p.user_id=u.id ";
                $sql .= $kind === 'team'
                    ? 'JOIN program_members m ON m.user_id=u.id WHERE m.partner_id=? AND p.is_partner=0'
                    : 'WHERE p.referred_by_user_id=? AND p.is_partner=0 AND NOT EXISTS (SELECT 1 FROM program_members m WHERE m.user_id=u.id)';
                $sql .= ' AND u.deleted_at IS NULL';
                $params = [$user];
            } elseif ($kind === 'ledger') {
                $sortColumn = match ($sort) {
                    'depth' => 'l.id', 'user_name' => 'user_name', 'created_at' => 'l.created_at',
                    default => throw new DomainException('Неизвестная сортировка журнала'),
                };
                if (!\in_array($direction, ['asc', 'desc'], true)) {
                    throw new DomainException('Неизвестное направление сортировки');
                }
                $orderBy = $sort === 'depth' ? 'l.id DESC' : $sortColumn . ' ' . strtoupper($direction) . ', l.created_at DESC, l.id DESC';
                $sql = "SELECT l.*, COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))),''),'Имя не указано') user_name, CASE WHEN p.is_partner=1 THEN 'Партнёр' WHEN EXISTS (SELECT 1 FROM program_members m WHERE m.user_id=p.user_id) THEN 'Участник команды' WHEN p.referred_by_user_id IS NOT NULL THEN 'Реферал' ELSE 'Пользователь' END participant_type FROM program_ledger l LEFT JOIN users u ON u.id=l.user_id LEFT JOIN user_profiles p ON p.user_id=l.user_id WHERE 1=1";
                if ($user !== null) {
                    $sql .= ' AND l.user_id=?';
                    $params[] = $user;
                }
                foreach ([$dateFrom, $dateTo] as $date) {
                    if ($date === null) {
                        continue;
                    }
                    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('UTC'));
                    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
                        throw new DomainException('Укажите корректную дату в формате ГГГГ-ММ-ДД');
                    }
                }
                if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
                    throw new DomainException('Начало периода не может быть позже окончания');
                }
                if ($dateFrom !== null) {
                    $sql .= ' AND l.created_at>=?';
                    $params[] = $dateFrom . ' 00:00:00';
                }
                if ($dateTo !== null) {
                    $sql .= ' AND l.created_at<?';
                    $params[] = new DateTimeImmutable($dateTo, new DateTimeZone('UTC'))->modify('+1 day')->format('Y-m-d H:i:s');
                }
            } elseif ($kind === 'sales') {
                $sql = "SELECT o.id,o.status,o.delivered_at,o.snapshot,SUM(CASE WHEN l.state<>'void' THEN l.amount_minor ELSE 0 END) earned_minor FROM program_orders o JOIN program_ledger l ON l.order_id=o.id WHERE l.wallet='commission'" . ($user === null ? '' : ' AND l.user_id=?') . ' GROUP BY o.id,o.status,o.delivered_at,o.snapshot';
                if ($user !== null) {
                    $params = [$user];
                }
            } else {
                $table = match ($kind) {
                    'withdrawals' => 'program_withdrawals','audit' => 'program_audit',default => throw new DomainException('Unknown list')
                };
                $sql = 'SELECT * FROM ' . $table . ($user === null ? '' : ' WHERE user_id=?');
                if ($user !== null) {
                    $params = [$user];
                }
            }
            if ($wallet !== null) {
                if ($kind !== 'ledger' || !\in_array($wallet, ['shopping', 'commission'], true)) {
                    throw new DomainException('Invalid ledger wallet');
                }
                $sql .= ' AND l.wallet=?';
                $params[] = $wallet;
            }
            $rows = $this->db->fetchAllAssociative($sql . ' ORDER BY ' . $orderBy . ' LIMIT ' . $limit . ' OFFSET ' . $offset, $params);
            foreach ($rows as &$row) {
                if ($kind === 'sales') {
                    $snapshot = $this->decode($row['snapshot']);
                    $row['goods_minor'] = array_sum(array_column($snapshot['items'], 'paidMinor'));
                    if ($snapshot['fullyRefunded'] ?? false) {
                        $row['status'] = 'refunded';
                    }
                    unset($row['snapshot']);
                }
                foreach (['amount_minor', 'earned_minor', 'user_id', 'buyer_id', 'depth'] as $number) {
                    if (isset($row[$number])) {
                        $row[$number] = (int)$row[$number];
                    }
                }
                foreach (['details', 'payload'] as $key) {
                    if (isset($row[$key])) {
                        $row[$key] = $this->decode($row[$key]);
                    }
                }if ($kind === 'withdrawals') {
                    $row['reason'] = $row['details']['decisionReason'] ?? null;
                }
                if (\array_key_exists('referred_by_user_id', $row)) {
                    $row['is_referral'] = $row['isReferral'] = $row['referred_by_user_id'] !== null;
                }
            }
            unset($row);
            return ['items' => $rows, 'page' => max(1, $page), 'limit' => $limit];
        });
    }

    public function setPartnerStatus(int $user, bool $partner, int $actor, string $reason): void
    {
        if (trim($reason) === '') {
            throw new DomainException('Reason required');
        }
        $this->atomic(function () use ($user, $partner, $actor, $reason): void {
            $old = $this->db->fetchAssociative('SELECT is_partner,referred_by_user_id FROM user_profiles WHERE user_id=?', [$user]);
            if (!$old) {
                throw new DomainException('User not found');
            }
            if ((bool)$old['is_partner'] === $partner) {
                return;
            }
            // Promotion removes membership; purchase customers and historical orders stay intact.
            if ($partner) {
                $this->db->delete('program_members', ['user_id' => $user]);
            } else {
                $this->db->delete('program_members', ['partner_id' => $user]);
                $this->db->delete('program_invitations', ['partner_id' => $user]);
            }
            $parent = $partner ? null : $old['referred_by_user_id'];
            $this->db->update('user_profiles', ['is_partner' => (int)$partner, 'referred_by_user_id' => $parent], ['user_id' => $user]);
            $this->audit($actor, 'partner_status', ['userId' => $user, 'before' => $old, 'isPartner' => $partner, 'reason' => $reason]);
        });
    }

    public function adjust(int $user, string $wallet, int $amount, string $reason, int $actor): void
    {
        if (!\in_array($wallet, ['shopping', 'commission'], true) || trim($reason) === '' || $amount === 0) {
            throw new DomainException('Wallet, nonzero amountMinor and reason required');
        }
        $this->atomic(function () use ($user, $wallet, $amount, $reason, $actor): void {
            $this->opening($user);
            $id = bin2hex(random_bytes(16));
            $this->entry('adjust:' . $id, $user, $wallet, $amount, 'adjustment', null, 'available', ['actorId' => $actor, 'reason' => $reason]);
            $this->audit($actor, 'adjustment', ['userId' => $user, 'wallet' => $wallet, 'amountMinor' => $amount, 'reason' => $reason]);
            $this->syncProfile($user);
        });
    }

    public function assertMutable(string $order): void
    {
        if ($this->db->fetchOne('SELECT id FROM program_orders WHERE id=?', [$order]) !== false) {
            throw new DomainException('Financial terms of snapshotted order are immutable; cancel and create a new order');
        }
    }

    private function safeParent(?int $buyer, ?int $parent): ?int
    {
        $seen = $buyer === null ? [] : [$buyer => true];
        $cursor = $parent;
        while ($cursor !== null) {
            if (isset($seen[$cursor])) {
                return null;
            }
            $seen[$cursor] = true;
            $row = $this->db->fetchAssociative('SELECT referred_by_user_id FROM user_profiles WHERE user_id=?', [$cursor]);
            if (!$row) {
                return null;
            }
            $cursor = $row['referred_by_user_id'] === null ? null : (int)$row['referred_by_user_id'];
        }
        return $parent;
    }

    private function recipients(?int $buyer, ?int $parent, array $rules, bool $buyerBonus, string $guestEmail = ''): array
    {
        $result = [];
        $participants = new ProgramParticipants($this->db);
        $buyerIdentity = $buyer === null ? null : $participants->identity($buyer);
        $eligible = function (int $id) use ($buyer, $guestEmail): bool {
            $u = $this->db->fetchAssociative('SELECT email,status,deleted_at FROM users WHERE id=?', [$id]);
            return $id !== $buyer && $u && $u['deleted_at'] === null && (int)$u['status'] === 1
                && ($guestEmail === '' || $guestEmail !== mb_strtolower(trim((string)$u['email'])));
        };
        $add = static function (int $id, string $wallet, string $kind, int $bps) use (&$result, $eligible): void {
            if ($eligible($id) && $bps > 0) {
                $result[] = ['userId' => $id, 'wallet' => $wallet, 'kind' => $kind, 'bps' => $bps];
            }
        };
        if (new SiteSettings($this->db)->bool('referral_enabled')) {
            if ($buyerIdentity && $buyerIdentity['isTeamMember']) {
                // A member's own purchases belong only to their explicit team partner.
                $add($buyerIdentity['teamPartnerId'], 'commission', 'team', $rules['directBps']);
            } elseif ($buyerIdentity === null || !$buyerIdentity['isPartner']) {
                if ($parent !== null && $eligible($parent)) {
                    $owner = $participants->identity($parent);
                    if ($owner['canEarnCommission']) {
                        $add($parent, 'commission', 'direct', $rules['directBps']);
                        if ($owner['isTeamMember']) {
                            $add($owner['teamPartnerId'], 'commission', 'team', $rules['teamBps']);
                        }
                    } else {
                        $add($parent, 'shopping', 'referral_bonus', $rules['referralBonusBps']);
                    }
                }
            }
        }
        if ($buyer !== null && $buyerBonus) {
            $result[] = ['userId' => $buyer, 'wallet' => 'shopping', 'kind' => 'buyer', 'bps' => $rules['buyerBps']];
        }
        return $result;
    }

    private function now(): string
    {
        return ($this->clock === null ? UtcClock::now() : ($this->clock)())->format('Y-m-d H:i:s');
    }

    private function json(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function decode(string $data): array
    {
        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }

    private function lockSql(string $sql): string
    {
        return $sql . ($this->db->getDatabasePlatform() instanceof SQLitePlatform ? '' : ' FOR UPDATE');
    }

    private function audit(?int $actor, string $kind, array $payload): void
    {
        $this->db->insert('program_audit', ['id' => bin2hex(random_bytes(16)), 'actor_id' => $actor, 'kind' => $kind, 'payload' => $this->json($payload), 'created_at' => $this->now()]);
    }

    private function entry(string $id, int $user, string $wallet, int $amount, string $kind, ?string $order = null, string $state = 'available', array $details = [], ?string $availableAt = null): void
    {
        $this->db->insert('program_ledger', ['id' => $id, 'user_id' => $user, 'wallet' => $wallet, 'amount_minor' => $amount, 'kind' => $kind, 'order_id' => $order, 'state' => $state, 'details' => $this->json($details), 'created_at' => $this->now(), 'available_at' => $availableAt]);
    }

    private function opening(int $user): void
    {
        if ($this->db->fetchOne('SELECT id FROM program_ledger WHERE id=?', ['opening:' . $user]) !== false) {
            return;
        }
        $balance = $this->db->fetchOne($this->lockSql('SELECT bonus_balance FROM user_profiles WHERE user_id=?'), [$user]);
        if ($balance === false) {
            throw new DomainException('User profile not found');
        }
        $this->entry('opening:' . $user, $user, 'shopping', (int)$balance * 100, 'legacy_opening');
    }

    private function balance(int $user, string $wallet): array
    {
        $rows = $this->db->fetchAllAssociative('SELECT state,SUM(amount_minor) amount FROM program_ledger WHERE user_id=? AND wallet=? GROUP BY state', [$user, $wallet]);
        $b = ['available' => 0, 'pending' => 0, 'reserved' => 0];
        foreach ($rows as $r) {
            $b[$r['state']] = (int)$r['amount'];
        }
        return ['availableMinor' => $b['available'] + $b['reserved'], 'pendingMinor' => $b['pending'], 'reservedMinor' => -$b['reserved'], 'debtMinor' => max(0, -($b['available'] + $b['reserved']))];
    }

    private function syncProfile(int $user): void
    {
        $amount = (int)$this->db->fetchOne("SELECT COALESCE(SUM(amount_minor),0) FROM program_ledger WHERE user_id=? AND wallet='shopping' AND state IN ('available','reserved')", [$user]);
        $this->db->update('user_profiles', ['bonus_balance' => intdiv(max(0, $amount), 100)], ['user_id' => $user]);
    }

    private function order(string $id): array|false
    {
        return $this->db->fetchAssociative($this->lockSql('SELECT * FROM program_orders WHERE id=?'), [$id]);
    }
}
