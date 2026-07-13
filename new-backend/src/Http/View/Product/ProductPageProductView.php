<?php

declare(strict_types=1);

namespace App\Http\View\Product;

final readonly class ProductPageProductView
{
    /**
     * @param list<string> $images
     * @param list<ProductImageView> $imageItems
     * @param list<string> $features
     * @param list<ProductVariantView> $variants
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public ?string $h1,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public float $price,
        public ?float $oldPrice,
        public string $description,
        public string $descriptionHtml,
        public ?string $shortDescription,
        public string $categoryId,
        public string $category,
        public ?string $categorySlug,
        public string $image,
        public ?string $imageAlt,
        public array $images,
        public array $imageItems,
        public ?string $badge,
        public string $weight,
        public ?string $sku,
        public ?string $gtin,
        public string $availability,
        public ?string $ingredients,
        public array $features,
        public ?string $wbLink,
        public ?string $ozonLink,
        public array $variants = [],
        public float $ratingRate = 0.0,
        public int $ratingCount = 0,
    ) {}
}
