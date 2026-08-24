<?php

declare(strict_types=1);

namespace App\Modules\Order\Command\Order\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateOrderCommand
{
    /**
     * @param list<array{productId?: int|string, product_id?: int|string, quantity?: int|string}> $items
     * @param array{
     *     name?: string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     city?: string|null,
     *     address?: string|null,
     *     postal_code?: string|null,
     *     postalCode?: string|null
     * } $shippingAddress
     */
    public function __construct(
        public ?int $userId,
        #[Assert\NotBlank]
        public array $shippingAddress,
        #[Assert\NotBlank]
        public string $paymentMethod,
        #[Assert\NotBlank]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
        public array $items = [],
        public ?string $orderId = null,
        public ?int $total = null,
        public ?int $bonusUsed = null,
        public bool $useBonuses = false,
        public string $deliveryMethod = 'cdek',
        public ?string $promoCode = null,
        public string $status = 'pending',
        public string $paymentStatus = 'pending',
        public ?string $referredBy = null,
    ) {}
}
