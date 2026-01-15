<?php

declare(strict_types=1);

namespace App\Modules\Query\Orders\GetById;

final readonly class Query
{
    public function __construct(
        public string $orderId,
    ) {}
}
