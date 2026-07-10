<?php

declare(strict_types=1);

namespace App\Http\View\Order;

final readonly class OrderCardView
{
    /**
     * @param list<OrderItemView> $products
     */
    public function __construct(
        public int $id,
        public string $status,
        public int $userId,
        public float $totalAmount,
        public string $orderDate,
        public array $products,
    ) {}
}
