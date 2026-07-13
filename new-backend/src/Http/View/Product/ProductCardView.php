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
        public ?float $oldPrice,
        public string $description,
        public ?string $shortDescription,
        public string $categoryId,
        public string $category,
        public string $brand,
        public int $stock,
        public string $image,
        public ?string $badge,
        public string $weight,
        public float $ratingRate = 0.0,
        public int $ratingCount = 0,
        public ?string $imageAlt = null,
        public ?string $categorySlug = null,
    ) {}
}
