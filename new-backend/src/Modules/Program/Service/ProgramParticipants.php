<?php

declare(strict_types=1);

namespace App\Modules\Program\Service;

use Doctrine\DBAL\Connection;
use DomainException;

final readonly class ProgramParticipants
{
    public function __construct(private Connection $db) {}

    public function identity(int $user): array
    {
        $p = $this->db->fetchAssociative('SELECT p.*,u.status,u.deleted_at FROM user_profiles p JOIN users u ON u.id=p.user_id WHERE p.user_id=?', [$user]);
        if (!$p || $p['deleted_at'] !== null) {
            throw new DomainException('Пользователь не найден');
        }
        return [
            'userId' => $user,
            'isPartner' => (bool)$p['is_partner'],
            'isReferral' => $p['referred_by_user_id'] !== null,
            'parentId' => $p['referred_by_user_id'] === null ? null : (int)$p['referred_by_user_id'],
            'referralCode' => $p['referral_code'],
        ];
    }

    public function requirePartner(int $user): void
    {
        if (!$this->identity($user)['isPartner']) {
            throw new DomainException('Этот раздел доступен только партнёру');
        }
    }
}
