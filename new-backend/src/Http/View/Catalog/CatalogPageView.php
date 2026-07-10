<?php

declare(strict_types=1);

namespace App\Http\View\Catalog;

use App\Http\View\Home\HomeCategoryView;
use App\Http\View\PageMetaView;
use App\Http\View\Product\ProductCardView;

final readonly class CatalogPageView
{
    /**
     * @param list<ProductCardView> $products
     * @param list<HomeCategoryView> $categories
     */
    public function __construct(
        public PageMetaView $meta,
        public array $products,
        public array $categories,
        public int $categoriesTotal,
        public ?string $selectedCategory,
    ) {}
}
