<?php

declare(strict_types=1);

namespace App\Modules\Order\Entity\OrderItem;

interface OrderItemRepository
{
    public function add(OrderItem $item): void;

    public function remove(OrderItem $item): void;

    /**
     * @return list<OrderItem>
     */
    public function findByOrderId(string $orderId): array;
}
