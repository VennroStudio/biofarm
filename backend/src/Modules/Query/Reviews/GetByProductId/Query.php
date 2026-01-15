<?php

declare(strict_types=1);

namespace App\Modules\Query\Reviews\GetByProductId;

final readonly class Query
{
    public function __construct(
        public int $productId,
        public bool $onlyApproved = true,
    ) {}
}
