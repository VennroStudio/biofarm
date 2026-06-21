<?php

declare(strict_types=1);

namespace App\Modules\Order\Command\Order\Update;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateOrderCommand
{
    /**
     * @param list<array{productId?: int|string, product_id?: int|string, productName?: string, product_name?: string, price?: int|string, quantity?: int|string}>|null $items
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
        #[Assert\NotBlank]
        public string $orderId,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $userId,
        #[Assert\NotBlank]
        public string $status,
        #[Assert\NotBlank]
        public string $paymentStatus,
        #[Assert\NotBlank]
        #[Assert\PositiveOrZero]
        public int $total,
        #[Assert\NotBlank]
        public array $shippingAddress,
        #[Assert\NotBlank]
        public string $paymentMethod,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
        public ?array $items = null,
        public int $bonusUsed = 0,
        public int $bonusEarned = 0,
        public ?string $trackingNumber = null,
        public ?string $referredBy = null,
    ) {}
}
