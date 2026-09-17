<?php

declare(strict_types=1);

namespace App\Modules\Program\Service;

use Doctrine\DBAL\Connection;
use DomainException;

/** Team membership is explicit and independent from purchase attribution. */
final readonly class ProgramParticipants
{
    public function __construct(private Connection $db) {}

    public function identity(int $user): array
    {
        $p = $this->db->fetchAssociative('SELECT p.*,u.status,u.deleted_at FROM user_profiles p JOIN users u ON u.id=p.user_id WHERE p.user_id=?', [$user]);
        if (!$p || $p['deleted_at'] !== null) {
            throw new DomainException('Пользователь не найден');
        }
        $partner = $this->db->fetchOne('SELECT m.partner_id FROM program_members m JOIN user_profiles p ON p.user_id=m.partner_id JOIN users u ON u.id=p.user_id WHERE m.user_id=? AND p.is_partner=1 AND u.deleted_at IS NULL AND u.status=1', [$user]);
        $isPartner = (bool)$p['is_partner'];
        $teamPartnerId = !$isPartner && $partner !== false ? (int)$partner : null;
        $hasCommissionHistory = (bool)$this->db->fetchOne("SELECT 1 FROM program_ledger WHERE user_id=? AND wallet='commission' LIMIT 1", [$user]);
        return ['hasCommissionHistory' => $hasCommissionHistory, 'userId' => $user, 'isPartner' => $isPartner, 'isTeamMember' => $teamPartnerId !== null, 'teamPartnerId' => $teamPartnerId, 'isReferral' => !$isPartner && $teamPartnerId === null && $p['referred_by_user_id'] !== null, 'parentId' => $p['referred_by_user_id'] === null ? null : (int)$p['referred_by_user_id'], 'referralCode' => $p['referral_code'], 'canEarnCommission' => $isPartner || $teamPartnerId !== null];
    }

    public function requireParticipant(int $user, bool $partnerOnly = false): array
    {
        $identity = $this->identity($user);
        if ($partnerOnly ? !$identity['isPartner'] : !$identity['canEarnCommission']) {
            throw new DomainException('Этот раздел доступен только ' . ($partnerOnly ? 'партнёру' : 'партнёру или участнику команды'));
        }
        return $identity;
    }

    public function requireCommissionAccess(int $user): array
    {
        $identity = $this->identity($user);
        if (!$identity['canEarnCommission'] && !$identity['hasCommissionHistory']) {
            throw new DomainException('Нет денежных начислений для просмотра или выплаты');
        }
        return $identity;
    }

    public function invite(int $user): array
    {
        $this->requireParticipant($user, true);
        $code = $this->db->fetchOne('SELECT code FROM program_invitations WHERE partner_id=?', [$user]);
        if ($code === false) {
            $code = bin2hex(random_bytes(24));
            $this->db->insert('program_invitations', ['code' => $code, 'partner_id' => $user]);
        }
        return ['code' => $code];
    }

    public function invitation(string $code): array
    {
        if (!preg_match('/^[a-f0-9]{48}$/D', $code)) {
            throw new DomainException('Приглашение недействительно');
        }
        $p = $this->db->fetchAssociative('SELECT u.id,u.first_name,u.last_name FROM program_invitations i JOIN user_profiles p ON p.user_id=i.partner_id JOIN users u ON u.id=p.user_id WHERE i.code=? AND p.is_partner=1 AND u.status=1 AND u.deleted_at IS NULL', [$code]);
        if (!$p) {
            throw new DomainException('Приглашение недействительно');
        }
        return ['partnerId' => (int)$p['id'], 'partnerName' => trim($p['first_name'] . ' ' . $p['last_name'])];
    }

    /** Caller holds the program finance lock to serialize membership and role changes. */
    public function join(int $user, string $code, bool $consent): array
    {
        if (!$consent) {
            throw new DomainException('Подтвердите вступление в команду');
        }
        $invite = $this->invitation($code);
        $identity = $this->identity($user);
        if ($identity['isPartner'] || $invite['partnerId'] === $user) {
            throw new DomainException('Партнёр не может вступить в чужую команду');
        }
        $existing = $this->db->fetchOne('SELECT partner_id FROM program_members WHERE user_id=?', [$user]);
        if ($existing !== false && (int)$existing !== $invite['partnerId']) {
            throw new DomainException('Вы уже состоите в другой команде');
        }
        if ($existing === false) {
            $this->db->insert('program_members', ['user_id' => $user, 'partner_id' => $invite['partnerId'], 'joined_at' => gmdate('Y-m-d H:i:s')]);
            $this->db->insert('program_audit', ['id' => bin2hex(random_bytes(16)), 'actor_id' => $user, 'kind' => 'team_joined', 'payload' => json_encode(['partnerId' => $invite['partnerId']], JSON_THROW_ON_ERROR), 'created_at' => gmdate('Y-m-d H:i:s')]);
        }
        return $this->identity($user);
    }
}
