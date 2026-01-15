<?php

declare(strict_types=1);

namespace App\Modules\Query\Users\GetByEmail;

final readonly class Query
{
    public function __construct(
        public string $email,
    ) {}
}
