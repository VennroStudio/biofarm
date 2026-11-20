<?php

declare(strict_types=1);

namespace App\Modules\User\Query\User\GetById;

final readonly class UserGetByIdQuery
{
    public function __construct(
        public int $id,
    ) {}
}
