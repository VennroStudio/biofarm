<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\Product\Delete;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeleteProductCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $productId,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
    ) {}
}
