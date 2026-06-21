<?php

declare(strict_types=1);

namespace App\Modules\Order\Command\Order\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateOrderCommand
{
    /**
     * @param list<array{productId?: int|string, product_id?: int|string, productName?: string, product_name?: string, price?: int|string, quantity?: int|string}> $items
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
        public int $userId,
        #[Assert\NotBlank]
        #[Assert\Positive]
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
        public array $items = [],
        public ?string $orderId = null,
        public int $bonusUsed = 0,
        public string $status = 'pending',
        public string $paymentStatus = 'pending',
        public ?string $referredBy = null,
    ) {}
}
