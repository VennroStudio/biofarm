<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware\Identity;

use App\Modules\User\Entity\User\Fields\Enums\UserRole;

/**
 * Текущий пользователь из JWT (request attribute после Authenticate).
 */
final readonly class Identity
{
    public function __construct(
        public int $id,
        public string $firstName,
        public UserRole $role,
    ) {}
}
