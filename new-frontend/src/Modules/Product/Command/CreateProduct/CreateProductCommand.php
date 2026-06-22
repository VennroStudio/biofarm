<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\CreateProduct;

final readonly class CreateProductCommand
{
    /**
     * @param array<string, bool|float|int|string> $specs
     */
    public function __construct(
        public string $title,
        public float $price,
        public string $description,
        public string $category,
        public string $brand,
        public int $stock,
        public string $image,
        public array $specs = [],
    ) {}
}
