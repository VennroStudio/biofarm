<?php

declare(strict_types=1);

namespace App\Modules\Command\Review\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $reviewId,
        #[Assert\NotBlank]
        public int $productId,
        #[Assert\NotBlank]
        public string $userName,
        #[Assert\NotBlank]
        #[Assert\Range(min: 1, max: 5)]
        public int $rating,
        #[Assert\NotBlank]
        public string $text,
        #[Assert\NotBlank]
        public string $source,
        public ?string $userId = null,
        /** @var string[]|null */
        public ?array $images = null,
        public bool $isApproved = false,
    ) {}
}
