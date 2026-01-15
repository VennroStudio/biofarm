<?php

declare(strict_types=1);

namespace App\Modules\Command\Order\UpdateStatus;

use App\Modules\Entity\Order\Order;
use App\Modules\Entity\Order\OrderRepository;

final readonly class Handler
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {}

    public function handle(Command $command): Order
    {
        $order = $this->orderRepository->getById($command->orderId);

        $order->updateStatus($command->status);

        return $order;
    }
}
