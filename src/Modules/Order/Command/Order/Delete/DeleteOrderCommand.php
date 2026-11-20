<?php

declare(strict_types=1);

namespace App\Modules\Order\Command\Order\Delete;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeleteOrderCommand
{
    public function __construct(
        #[Assert\NotBlank]
        public string $orderId,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
    ) {}
}
