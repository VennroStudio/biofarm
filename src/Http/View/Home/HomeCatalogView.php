<?php

declare(strict_types=1);

namespace App\Http\View\Home;

use App\Http\View\Product\ProductCardView;

final readonly class HomeCatalogView
{
    /**
     * @param list<ProductCardView> $products
     * @param list<HomeCategoryView> $categories
     */
    public function __construct(
        public array $products,
        public array $categories,
        public ?string $selectedCategory,
        public int $currentPage,
        public int $totalPages,
        public int $pageSize,
    ) {}

    public function url(int $page = 1, ?string $category = null): string
    {
        $query = http_build_query(array_filter([
            'category' => $category,
            'page' => $page > 1 ? $page : null,
            'per_page' => $this->pageSize === 6 ? 6 : null,
        ], static fn (mixed $value): bool => $value !== null));

        return '/' . ($query === '' ? '' : '?' . $query) . '#catalog';
    }
}
