<?php

declare(strict_types=1);

namespace App\Modules\Command\Order\Create;

use App\Modules\Entity\Order\Order;
use App\Modules\Entity\Order\OrderRepository;

final readonly class Handler
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {}

    public function handle(Command $command): Order
    {
        $order = Order::create(
            id: $command->orderId,
            userId: $command->userId,
            total: $command->total,
            shippingAddress: $command->shippingAddress,
            paymentMethod: $command->paymentMethod,
            bonusUsed: $command->bonusUsed,
            status: $command->status,
            paymentStatus: $command->paymentStatus,
            referredBy: $command->referredBy,
        );

        $this->orderRepository->add($order);

        return $order;
    }
}
