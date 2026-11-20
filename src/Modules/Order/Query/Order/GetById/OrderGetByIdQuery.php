<?php

declare(strict_types=1);

namespace App\Modules\Order\Query\Order\GetById;

final readonly class OrderGetByIdQuery
{
    public function __construct(
        public string $orderId,
    ) {}
}
