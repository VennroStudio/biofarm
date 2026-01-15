<?php

declare(strict_types=1);

namespace App\Modules\Query\Orders\GetById;

use App\Modules\Entity\Order\Order;
use App\Modules\Entity\Order\OrderRepository;

final readonly class Fetcher
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {}

    public function fetch(Query $query): ?Order
    {
        return $this->orderRepository->findById($query->orderId);
    }
}
