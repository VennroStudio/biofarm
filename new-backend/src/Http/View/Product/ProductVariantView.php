<?php

declare(strict_types=1);

namespace App\Http\View\Product;

final readonly class ProductVariantView
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public string $image,
        public ?string $imageAlt,
        public string $label,
        public string $weight,
        public bool $isCurrent,
    ) {}
}
