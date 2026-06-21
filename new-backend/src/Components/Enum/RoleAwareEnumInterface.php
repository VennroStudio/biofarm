<?php

declare(strict_types=1);

namespace App\Components\Enum;

use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use BackedEnum;

interface RoleAwareEnumInterface
{
    /**
     * @return list<BackedEnum&EnumInterface>
     */
    public static function casesForRole(UserRole $role): array;
}
