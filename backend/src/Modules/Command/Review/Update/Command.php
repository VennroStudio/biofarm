<?php

declare(strict_types=1);

namespace App\Modules\Command\Review\Update;

final readonly class Command
{
    public function __construct(
        public string $reviewId,
        public int $productId,
        public string $userName,
        public int $rating,
        public string $text,
        public string $source,
        public ?string $userId = null,
        public ?array $images = null,
    ) {}
}
