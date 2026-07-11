<?php

declare(strict_types=1);

namespace App\Http\Unifier\Catalog;

use App\Http\Unifier\Product\ProductCatalogDataProvider;
use App\Http\View\Catalog\CatalogPageView;
use App\Http\View\Home\HomeCategoryView;
use App\Http\View\PageMetaView;

final readonly class CatalogPageUnifier
{
    private const int PRODUCTS_PER_PAGE = 12;
    private const array SORT_VALUES = ['default', 'price-asc', 'price-desc', 'name'];
    private const array VIEW_VALUES = ['grid', 'list'];

    public function __construct(
        private ProductCatalogDataProvider $catalogData,
    ) {}

    public function unify(
        ?string $selectedCategory = null,
        ?string $searchQuery = null,
        ?string $sortBy = null,
        ?string $viewMode = null,
        ?int $page = null,
    ): CatalogPageView {
        $category = $this->normalizeCategory($selectedCategory);
        $query = $this->normalizeSearch($searchQuery);
        $sort = $this->normalizeSort($sortBy);
        $view = $this->normalizeView($viewMode);

        $productsTotal = $this->catalogData->countProducts($category, $query);
        $totalPages = max(1, (int)ceil($productsTotal / self::PRODUCTS_PER_PAGE));
        $currentPage = min(max(1, $page ?? 1), $totalPages);
        $offset = ($currentPage - 1) * self::PRODUCTS_PER_PAGE;

        $products = $this->catalogData->products(
            selectedCategory: $category,
            limit: self::PRODUCTS_PER_PAGE,
            search: $query,
            sort: $sort,
            offset: $offset,
        );
        $categories = $this->catalogData->categories();

        return new CatalogPageView(
            meta: new PageMetaView(
                title: 'Каталог — БИОФАРМ',
                description: 'Каталог натуральной продукции БИОФАРМ.',
            ),
            products: $products,
            categories: $categories,
            categoriesTotal: array_sum(array_map(
                static fn (HomeCategoryView $category): int => $category->productsCount,
                $categories,
            )),
            selectedCategory: $category,
            productsTotal: $productsTotal,
            searchQuery: $query ?? '',
            sortBy: $sort,
            viewMode: $view,
            currentPage: $currentPage,
            totalPages: $totalPages,
            previousPage: $currentPage > 1 ? $currentPage - 1 : null,
            nextPage: $currentPage < $totalPages ? $currentPage + 1 : null,
            pageNumbers: $this->pageNumbers($currentPage, $totalPages),
            pageUrls: $this->pageUrls($this->pageNumbers($currentPage, $totalPages), $category, $query, $sort, $view),
            previousPageUrl: $currentPage > 1 ? $this->catalogUrl($category, $query, $sort, $view, $currentPage - 1) : null,
            nextPageUrl: $currentPage < $totalPages ? $this->catalogUrl($category, $query, $sort, $view, $currentPage + 1) : null,
            allCategoryUrl: $this->catalogUrl(null, $query, $sort, $view, 1),
            categoryUrls: $this->categoryUrls($categories, $query, $sort, $view),
            viewUrls: [
                'grid' => $this->catalogUrl($category, $query, $sort, 'grid', 1),
                'list' => $this->catalogUrl($category, $query, $sort, 'list', 1),
            ],
            resetUrl: '/catalog#catalog',
        );
    }

    private function normalizeCategory(?string $category): ?string
    {
        $value = trim((string)$category);

        return $value === '' || $value === 'all' ? null : $value;
    }

    private function normalizeSearch(?string $query): ?string
    {
        $value = trim((string)$query);

        return $value === '' ? null : mb_substr($value, 0, 80);
    }

    private function normalizeSort(?string $sort): string
    {
        $value = trim((string)$sort);

        return \in_array($value, self::SORT_VALUES, true) ? $value : 'default';
    }

    private function normalizeView(?string $view): string
    {
        $value = trim((string)$view);

        return \in_array($value, self::VIEW_VALUES, true) ? $value : 'grid';
    }

    /**
     * @return list<int>
     */
    private function pageNumbers(int $currentPage, int $totalPages): array
    {
        $pages = [];
        for ($page = 1; $page <= $totalPages; ++$page) {
            if ($page === 1 || $page === $totalPages || abs($page - $currentPage) <= 2) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    /**
     * @param list<int> $pages
     * @return array<int, string>
     */
    private function pageUrls(array $pages, ?string $category, ?string $query, string $sort, string $view): array
    {
        $urls = [];
        foreach ($pages as $page) {
            $urls[$page] = $this->catalogUrl($category, $query, $sort, $view, $page);
        }

        return $urls;
    }

    /**
     * @param list<HomeCategoryView> $categories
     * @return array<string, string>
     */
    private function categoryUrls(array $categories, ?string $query, string $sort, string $view): array
    {
        $urls = [];
        foreach ($categories as $item) {
            $urls[$item->id] = $this->catalogUrl($item->id, $query, $sort, $view, 1);
        }

        return $urls;
    }

    private function catalogUrl(
        ?string $category,
        ?string $query,
        string $sort,
        string $view,
        int $page,
    ): string {
        $params = [
            'category' => $category,
            'q'        => $query,
            'sort'     => $sort !== 'default' ? $sort : null,
            'view'     => $view !== 'grid' ? $view : null,
            'page'     => $page > 1 ? $page : null,
        ];

        $params = array_filter(
            $params,
            static fn ($value): bool => $value !== null && $value !== '',
        );

        return '/catalog' . ($params !== [] ? '?' . http_build_query($params) : '') . '#catalog';
    }
}
