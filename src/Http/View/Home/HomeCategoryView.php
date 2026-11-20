<?php

declare(strict_types=1);

namespace App\Http\View\Home;

final readonly class HomeCategoryView
{
    public function __construct(
        public string $id,
        public string $name,
        public int $productsCount,
        public ?string $slug = null,
        public ?string $parentSlug = null,
    ) {}
}
