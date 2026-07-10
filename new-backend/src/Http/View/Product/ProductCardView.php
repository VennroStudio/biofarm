<?php

declare(strict_types=1);

namespace App\Http\View\Product;

final readonly class ProductCardView
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public float $price,
        public string $description,
        public string $category,
        public string $brand,
        public int $stock,
        public string $image,
        public float $ratingRate = 0.0,
        public int $ratingCount = 0,
    ) {}
}
