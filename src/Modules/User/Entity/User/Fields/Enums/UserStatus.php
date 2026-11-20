<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\User\Fields\Enums;

enum UserStatus: int
{
    case ACTIVE = 1;
    case PENDING_VERIFICATION = 2;
    case BANNED = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE               => 'Активен',
            self::PENDING_VERIFICATION => 'Ожидает верификации',
            self::BANNED               => 'Забанен',
        };
    }
}
