<?php

declare(strict_types=1);

namespace App\Modules\Query\Users\GetById;

final readonly class Query
{
    public function __construct(
        public int $userId,
    ) {}
}
