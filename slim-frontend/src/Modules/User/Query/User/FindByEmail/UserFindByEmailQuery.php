<?php

declare(strict_types=1);

namespace App\Modules\User\Query\User\FindByEmail;

final readonly class UserFindByEmailQuery
{
    public function __construct(
        public string $email,
    ) {}
}
