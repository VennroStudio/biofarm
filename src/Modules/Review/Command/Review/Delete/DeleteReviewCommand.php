<?php

declare(strict_types=1);

namespace App\Modules\Review\Command\Review\Delete;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeleteReviewCommand
{
    public function __construct(
        #[Assert\NotBlank]
        public string $reviewId,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
    ) {}
}
