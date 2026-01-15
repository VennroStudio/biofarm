<?php

declare(strict_types=1);

namespace App\Modules\Query\Reviews\GetAll;

final readonly class Query
{
    public function __construct(
        public bool $onlyApproved = true,
    ) {}
}
