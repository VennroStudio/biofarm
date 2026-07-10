<?php

declare(strict_types=1);

namespace App\Http\View\Product;

final readonly class ProductPageProductView
{
    /**
     * @param list<string> $images
     * @param list<string> $features
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public float $price,
        public ?float $oldPrice,
        public string $description,
        public string $descriptionHtml,
        public ?string $shortDescription,
        public string $categoryId,
        public string $category,
        public string $image,
        public array $images,
        public ?string $badge,
        public string $weight,
        public ?string $ingredients,
        public array $features,
        public ?string $wbLink,
        public ?string $ozonLink,
    ) {}
}
