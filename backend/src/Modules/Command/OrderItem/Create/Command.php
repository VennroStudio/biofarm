<?php

declare(strict_types=1);

namespace App\Modules\Command\OrderItem\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $orderId,
        #[Assert\NotBlank]
        public int $productId,
        #[Assert\NotBlank]
        public string $productName,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $price,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $quantity,
    ) {}
}
