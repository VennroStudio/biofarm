<?php

declare(strict_types=1);

namespace App\Modules\Query\Orders\GetByUserId;

use App\Modules\Entity\Order\Order;
use App\Modules\Entity\Order\OrderRepository;

final readonly class Fetcher
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {}

    /** @return Order[] */
    public function fetch(Query $query): array
    {
        return $this->orderRepository->findByUserId($query->userId);
    }
}
