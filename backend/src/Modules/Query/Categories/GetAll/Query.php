<?php

declare(strict_types=1);

namespace App\Modules\Query\Categories\GetAll;

final readonly class Query
{
    public function __construct(
        public bool $activeOnly = false,
    ) {}
}
