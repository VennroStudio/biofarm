<?php

declare(strict_types=1);

namespace App\Http\View\Review;

final readonly class ReviewCardView
{
    public function __construct(
        public string $id,
        public int $rating,
        public string $title,
        public string $content,
        public string $userId,
        public string $date,
    ) {}
}
