<?php

declare(strict_types=1);

namespace App\Modules\Order\Command\Order\Delete;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Modules\Order\Entity\Order\OrderRepository;
use App\Modules\Order\Entity\OrderItem\OrderItemRepository;
use App\Modules\Order\Permission\OrderPermission;
use App\Modules\Order\Service\OrderPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;

final readonly class DeleteOrderHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderItemRepository $orderItemRepository,
        private OrderPermissionService $permissionService,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    public function handle(DeleteOrderCommand $command): void
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: OrderPermission::DELETE,
        );

        $order = $this->orderRepository->getById($command->orderId);

        foreach ($this->orderItemRepository->findByOrderId($command->orderId) as $item) {
            $this->orderItemRepository->remove($item);
        }

        $this->orderRepository->remove($order);
        $this->cacher->deleteTag('orders');
        $this->cacher->delete('order_by_id_' . $command->orderId);
        $this->flusher->flush();
    }
}
