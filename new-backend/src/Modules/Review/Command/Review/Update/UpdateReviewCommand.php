<?php

declare(strict_types=1);

namespace App\Modules\Review\Command\Review\Update;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateReviewCommand
{
    public function __construct(
        #[Assert\NotBlank]
        public string $reviewId,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $productId,
        #[Assert\NotBlank(message: 'validation.review_user_name_required')]
        public string $userName,
        #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'validation.review_rating_invalid')]
        public int $rating,
        #[Assert\NotBlank(message: 'validation.review_text_required')]
        public string $text,
        #[Assert\NotBlank(message: 'validation.review_source_required')]
        public string $source,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
        public ?string $userId = null,
        /** @var list<string>|null */
        public ?array $images = null,
    ) {}
}
