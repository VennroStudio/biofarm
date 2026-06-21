<?php

declare(strict_types=1);

namespace App\Modules\Order\Command\Order\Update;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Modules\Order\Entity\Order\OrderRepository;
use App\Modules\Order\Entity\OrderItem\OrderItem;
use App\Modules\Order\Entity\OrderItem\OrderItemRepository;
use App\Modules\Order\Permission\OrderPermission;
use App\Modules\Order\Service\OrderPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;

final readonly class UpdateOrderHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderItemRepository $orderItemRepository,
        private OrderPermissionService $permissionService,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function handle(UpdateOrderCommand $command): void
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: OrderPermission::UPDATE,
        );

        $order = $this->orderRepository->getById($command->orderId);
        $order->edit(
            userId: $command->userId,
            status: $command->status,
            paymentStatus: $command->paymentStatus,
            total: $command->total,
            bonusUsed: $command->bonusUsed,
            bonusEarned: $command->bonusEarned,
            shippingAddress: $command->shippingAddress,
            paymentMethod: $command->paymentMethod,
            trackingNumber: $command->trackingNumber,
            referredBy: $command->referredBy,
        );

        $this->replaceItems($command);
        $this->deleteCache($command->orderId);
        $this->flusher->flush();
    }

    private function replaceItems(UpdateOrderCommand $command): void
    {
        if ($command->items === null) {
            return;
        }

        foreach ($this->orderItemRepository->findByOrderId($command->orderId) as $item) {
            $this->orderItemRepository->remove($item);
        }

        foreach ($command->items as $item) {
            $this->orderItemRepository->add(OrderItem::create(
                orderId: $command->orderId,
                productId: (int)($item['productId'] ?? $item['product_id'] ?? 0),
                productName: (string)($item['productName'] ?? $item['product_name'] ?? ''),
                price: (int)($item['price'] ?? 0),
                quantity: (int)($item['quantity'] ?? 0),
            ));
        }
    }

    private function deleteCache(string $orderId): void
    {
        $this->cacher->deleteTag('orders');
        $this->cacher->delete('order_by_id_' . $orderId);
    }
}
