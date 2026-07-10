<?php

declare(strict_types=1);

namespace App\Http\View\Home;

final readonly class HomeReviewView
{
    /**
     * @param list<string> $images
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $avatar,
        public int $rating,
        public string $text,
        public string $date,
        public string $product,
        public array $images,
    ) {}
}
