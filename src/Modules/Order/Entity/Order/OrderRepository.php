<?php

declare(strict_types=1);

namespace App\Modules\Order\Entity\Order;

interface OrderRepository
{
    public function add(Order $order): void;

    public function remove(Order $order): void;

    public function getById(string $id): Order;

    public function findById(string $id): ?Order;
}
