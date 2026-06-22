<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\UpdateProduct;

final readonly class UpdateProductCommand
{
    /**
     * @param array<string, bool|float|int|string>|null $specs
     */
    public function __construct(
        public int $id,
        public ?string $title = null,
        public ?float $price = null,
        public ?string $description = null,
        public ?string $category = null,
        public ?string $brand = null,
        public ?int $stock = null,
        public ?string $image = null,
        public ?array $specs = null,
    ) {}
}
