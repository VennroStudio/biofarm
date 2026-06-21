<?php

declare(strict_types=1);

namespace App\Components\Permission;

use BackedEnum;

final readonly class PermissionModel
{
    /**
     * @template T of BackedEnum
     * @param class-string<T> $permissionClass
     * @param callable(T): bool $resolver
     * @return array<string, bool>
     */
    public static function fromEnumClass(string $permissionClass, callable $resolver): array
    {
        $permissions = [];

        foreach ($permissionClass::cases() as $permission) {
            $permissions[(string) $permission->value] = $resolver($permission);
        }

        return $permissions;
    }
}
