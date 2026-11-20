<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\ProductCategory\Delete;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeleteProductCategoryCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $categoryId,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
    ) {}
}
