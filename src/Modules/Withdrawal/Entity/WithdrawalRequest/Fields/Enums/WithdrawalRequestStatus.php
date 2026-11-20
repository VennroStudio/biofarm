<?php

declare(strict_types=1);

namespace App\Modules\Withdrawal\Entity\WithdrawalRequest\Fields\Enums;

enum WithdrawalRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING  => 'Ожидает',
            self::APPROVED => 'Одобрена',
            self::REJECTED => 'Отклонена',
        };
    }
}
