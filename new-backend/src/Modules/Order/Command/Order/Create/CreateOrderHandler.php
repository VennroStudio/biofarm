<?php

declare(strict_types=1);

namespace App\Modules\Order\Command\Order\Create;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Components\Id\ReadableIdGenerator;
use App\Modules\Order\Entity\Order\Order;
use App\Modules\Order\Entity\Order\OrderRepository;
use App\Modules\Order\Entity\OrderItem\OrderItem;
use App\Modules\Order\Entity\OrderItem\OrderItemRepository;
use App\Modules\Order\Permission\OrderPermission;
use App\Modules\Order\Service\OrderPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use DateMalformedStringException;
use Random\RandomException;

final readonly class CreateOrderHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderItemRepository $orderItemRepository,
        private ReadableIdGenerator $idGenerator,
        private OrderPermissionService $permissionService,
        private UserProfileRepository $profileRepository,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    public function handle(CreateOrderCommand $command): string
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: OrderPermission::CREATE,
        );

        $orderId = $command->orderId !== null && trim($command->orderId) !== ''
            ? trim($command->orderId)
            : $this->idGenerator->generate('ORD');

        $order = Order::create(
            id: $orderId,
            userId: $command->userId,
            total: $command->total,
            shippingAddress: $command->shippingAddress,
            paymentMethod: $command->paymentMethod,
            bonusUsed: $command->bonusUsed,
            status: $command->status,
            paymentStatus: $command->paymentStatus,
            referredBy: $this->resolveReferredBy($command),
        );

        $this->orderRepository->add($order);

        foreach ($command->items as $item) {
            $this->orderItemRepository->add(OrderItem::create(
                orderId: $orderId,
                productId: (int)($item['productId'] ?? $item['product_id'] ?? 0),
                productName: (string)($item['productName'] ?? $item['product_name'] ?? ''),
                price: (int)($item['price'] ?? 0),
                quantity: (int)($item['quantity'] ?? 0),
            ));
        }

        $this->cacher->deleteTag('orders');
        $this->flusher->flush();

        return $orderId;
    }

    private function resolveReferredBy(CreateOrderCommand $command): ?string
    {
        $referredBy = trim((string)$command->referredBy);
        if ($referredBy !== '') {
            return $referredBy;
        }

        $profile = $this->profileRepository->findByUserId($command->userId);
        if ($profile?->referredByUserId === null) {
            return null;
        }

        return (string)$profile->referredByUserId;
    }
}
