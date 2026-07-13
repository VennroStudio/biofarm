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
     * @param list<CatalogFacetView> $componentFilters
     * @param list<CatalogFacetView> $purposeFilters
     * @param list<int> $pageNumbers
     * @param array<int, string> $pageUrls
     * @param array<string, string> $categoryUrls
     * @param array<string, string> $componentFilterUrls
     * @param array<string, string> $purposeFilterUrls
     * @param array<string, string> $viewUrls
     */
    public function __construct(
        public PageMetaView $meta,
        public array $products,
        public array $categories,
        public int $categoriesTotal,
        public string $catalogPath,
        public string $catalogEyebrow,
        public string $catalogH1,
        public string $catalogLead,
        public ?string $introText,
        public ?string $bottomText,
        public array $componentFilters,
        public array $purposeFilters,
        public ?string $activeComponentSlug,
        public ?string $activePurposeSlug,
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
        public array $componentFilterUrls,
        public array $purposeFilterUrls,
        public array $viewUrls,
        public string $resetUrl,
    ) {}
}
