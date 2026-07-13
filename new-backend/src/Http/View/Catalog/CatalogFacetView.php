<?php

declare(strict_types=1);

namespace App\Http\View\Catalog;

final readonly class CatalogFacetView
{
    public function __construct(
        public string $slug,
        public string $name,
        public int $productsCount,
    ) {}
}
