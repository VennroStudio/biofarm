<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\UserToken\Fields\Enums;

enum UserTokenState: string
{
    case ACTIVE = 'active';
    case REVOKED = 'revoked';
    case USED = 'used';
    case EXPIRED = 'expired';
}
