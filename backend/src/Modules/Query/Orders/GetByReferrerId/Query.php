<?php

declare(strict_types=1);

namespace App\Modules\Query\Orders\GetByReferrerId;

final readonly class Query
{
    public function __construct(
        public int $referrerId,
    ) {}
}
