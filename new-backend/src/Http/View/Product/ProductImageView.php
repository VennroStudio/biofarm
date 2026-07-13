<?php

declare(strict_types=1);

namespace App\Http\View\Product;

final readonly class ProductImageView
{
    public function __construct(
        public string $path,
        public string $alt,
        public ?string $title = null,
        public ?int $width = null,
        public ?int $height = null,
    ) {}
}
