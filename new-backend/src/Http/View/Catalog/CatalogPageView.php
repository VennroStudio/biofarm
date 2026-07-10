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
     * @param list<int> $pageNumbers
     * @param array<int, string> $pageUrls
     * @param array<string, string> $categoryUrls
     * @param array<string, string> $viewUrls
     */
    public function __construct(
        public PageMetaView $meta,
        public array $products,
        public array $categories,
        public int $categoriesTotal,
        public ?string $selectedCategory,
        public int $productsTotal,
        public string $searchQuery,
        public string $sortBy,
        public string $viewMode,
        public int $currentPage,
        public int $totalPages,
        public ?int $previousPage,
        public ?int $nextPage,
        public array $pageNumbers,
        public array $pageUrls,
        public ?string $previousPageUrl,
        public ?string $nextPageUrl,
        public string $allCategoryUrl,
        public array $categoryUrls,
        public array $viewUrls,
        public string $resetUrl,
    ) {}
}
