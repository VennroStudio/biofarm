<?php

declare(strict_types=1);

namespace App\Components\Enum;

use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use BackedEnum;

final readonly class EnumModel
{
    public function __construct(
        public int|string $id,
        public string $label,
    ) {}

    /**
     * @template T of BackedEnum&EnumInterface
     * @param EnumInterface&BackedEnum $enum
     * @return EnumModel
     */
    public static function fromEnum(BackedEnum&EnumInterface $enum): self
    {
        return new self(
            id: $enum->value,
            label: $enum->getLabel(),
        );
    }

    /**
     * @template T of BackedEnum&EnumInterface
     * @param class-string<T> $enumClass
     * @return list<self>
     */
    public static function fromEnumClass(string $enumClass): array
    {
        return array_map(
            static fn($case) => self::fromEnum($case),
            $enumClass::cases(),
        );
    }

    /**
     * @template T of BackedEnum&EnumInterface&RoleAwareEnumInterface
     * @param class-string<T> $enumClass
     * @return list<self>
     */
    public static function fromEnumClassForRole(string $enumClass, UserRole $role): array
    {
        return array_map(
            static fn($case) => self::fromEnum($case),
            $enumClass::casesForRole($role),
        );
    }
}
