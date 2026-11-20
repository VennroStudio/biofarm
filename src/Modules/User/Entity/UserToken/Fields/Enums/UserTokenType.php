<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\UserToken\Fields\Enums;

enum UserTokenType: int
{
    case EMAIL_VERIFICATION = 1;
    case PASSWORD_RESET = 2;
    case REFRESH = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::EMAIL_VERIFICATION => 'Подтверждение email',
            self::PASSWORD_RESET     => 'Сброс пароля',
            self::REFRESH            => 'Обновление сессии',
        };
    }
}
