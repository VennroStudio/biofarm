<?php

declare(strict_types=1);

namespace App\Modules\Command\OrderItem\Create;

use App\Modules\Entity\OrderItem\OrderItem;
use App\Modules\Entity\OrderItem\OrderItemRepository;

final readonly class Handler
{
    public function __construct(
        private OrderItemRepository $orderItemRepository,
    ) {}

    public function handle(Command $command): OrderItem
    {
        $item = OrderItem::create(
            orderId: $command->orderId,
            productId: $command->productId,
            productName: $command->productName,
            price: $command->price,
            quantity: $command->quantity,
        );

        $this->orderItemRepository->add($item);

        return $item;
    }
}
