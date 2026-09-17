<?php

declare(strict_types=1);

namespace App\Modules\User\Service;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Program\Service\ProgramService;
use Doctrine\DBAL\Connection;

/** Read models for the admin user card. Amounts from the program are in kopecks. */
final readonly class AdminUserDetails
{
    public function __construct(private Connection $db, private ProgramService $program) {}

    public function get(int $user, string $section = 'profile', int $page = 1): array
    {
        if (!\in_array($section, ['profile', 'orders', 'referrals', 'team', 'shopping', 'commission', 'withdrawals'], true)) {
            throw new DomainExceptionModule('user', 'Неизвестная вкладка пользователя', 1, status: 422);
        }
        if (!$this->db->fetchOne('SELECT id FROM users WHERE id=? AND deleted_at IS NULL', [$user])) {
            throw new DomainExceptionModule('user', 'Пользователь не найден', 1, status: 404);
        }
        $dashboard = $this->program->dashboard($user);
        $result = ['balances' => $dashboard['balances'], 'items' => [], 'page' => max(1, $page), 'limit' => 20, 'count' => 0];
        if ($section === 'profile') {
            $result['items'] = $this->db->fetchAllAssociative('SELECT id,label,name,phone,city,address,postal_code,is_default FROM user_addresses WHERE user_id=? AND deleted_at IS NULL ORDER BY is_default DESC,id DESC', [$user]);
            $result['count'] = \count($result['items']);
            return $result;
        }
        $params = [$user];
        if ($section === 'orders') {
            $sql = 'SELECT id,created_at,status,payment_status,total,paid_at FROM orders WHERE user_id=?';
        } elseif ($section === 'referrals' || $section === 'team') {
            $sql = "SELECT u.id,u.first_name,u.last_name,u.email,u.created_at,
                (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) orders_count,
                (SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE o.user_id=u.id AND o.payment_status IN ('completed','paid')) paid_total
                FROM users u JOIN user_profiles p ON p.user_id=u.id ";
            $sql .= $section === 'team'
                ? 'JOIN program_members m ON m.user_id=u.id WHERE m.partner_id=? AND p.is_partner=0'
                : 'WHERE p.referred_by_user_id=? AND p.is_partner=0 AND NOT EXISTS (SELECT 1 FROM program_members m WHERE m.user_id=u.id)';
            $sql .= ' AND u.deleted_at IS NULL';
        } elseif ($section === 'withdrawals') {
            $sql = 'SELECT id,created_at,amount_minor,status,reference,details FROM program_withdrawals WHERE user_id=?';
        } else {
            $sql = 'SELECT id,created_at,kind,state,order_id,amount_minor,available_at FROM program_ledger WHERE user_id=? AND wallet=?';
            $params[] = $section;
        }
        $result['count'] = (int)$this->db->fetchOne('SELECT COUNT(*) FROM (' . $sql . ') entries', $params);
        $result['items'] = $this->db->fetchAllAssociative($sql . ' ORDER BY created_at DESC,id DESC LIMIT 20 OFFSET ' . (($result['page'] - 1) * 20), $params);
        foreach ($result['items'] as &$item) {
            if ($section === 'withdrawals') {
                $details = json_decode((string)$item['details'], true) ?: [];
                $item['reason'] = $details['decisionReason'] ?? null;
                $item['details'] = $details;
            }
        }
        unset($item);
        return $result;
    }
}
