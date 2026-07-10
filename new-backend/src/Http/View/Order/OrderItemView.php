<?php

declare(strict_types=1);

namespace App\Http\View\Order;

final readonly class OrderItemView
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}
}
