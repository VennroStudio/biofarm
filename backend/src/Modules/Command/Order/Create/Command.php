<?php

declare(strict_types=1);

namespace App\Modules\Command\Order\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $orderId,
        #[Assert\NotBlank]
        public int $userId,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $total,
        #[Assert\NotBlank]
        public array $shippingAddress,
        #[Assert\NotBlank]
        public string $paymentMethod,
        public int $bonusUsed = 0,
        public string $status = 'pending',
        public string $paymentStatus = 'pending',
        public ?string $referredBy = null,
    ) {}
}
