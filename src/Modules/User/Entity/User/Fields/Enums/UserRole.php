<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\User\Fields\Enums;

use App\Components\Enum\EnumInterface;

enum UserRole: int implements EnumInterface
{
    case ADMIN = 1;
    case DEVELOPER = 2;
    case EDITOR = 3;
    case USER = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN     => 'Администратор',
            self::DEVELOPER => 'Разработчик',
            self::EDITOR    => 'Редактор',
            self::USER      => 'Пользователь',
        };
    }
}
