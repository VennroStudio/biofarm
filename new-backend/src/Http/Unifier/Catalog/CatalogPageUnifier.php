<?php

declare(strict_types=1);

namespace App\Http\Unifier\Catalog;

use App\Components\Seo\JsonLdFactory;
use App\Components\Seo\SeoUrlGenerator;
use App\Http\Unifier\Product\ProductCatalogDataProvider;
use App\Http\View\Catalog\CatalogFacetView;
use App\Http\View\Catalog\CatalogPageView;
use App\Http\View\Home\HomeCategoryView;
use App\Http\View\PageMetaView;
use App\Modules\Page\Service\PageSeoProvider;

final readonly class CatalogPageUnifier
{
    private const int PRODUCTS_PER_PAGE = 12;
    private const array SORT_VALUES = ['default', 'price-asc', 'price-desc', 'name'];
    private const array VIEW_VALUES = ['grid', 'list'];

    public function __construct(
        private ProductCatalogDataProvider $catalogData,
        private SeoUrlGenerator $urls,
        private JsonLdFactory $jsonLd,
        private PageSeoProvider $pages,
    ) {}

    public function unify(
        ?string $selectedCategory = null,
        ?string $searchQuery = null,
        ?string $sortBy = null,
        ?string $viewMode = null,
        ?int $page = null,
        ?string $componentSlug = null,
        ?string $purposeSlug = null,
    ): CatalogPageView {
        $category = $this->catalogData->categoryId($selectedCategory);
        $query = $this->normalizeSearch($searchQuery);
        $sort = $this->normalizeSort($sortBy);
        $view = $this->normalizeView($viewMode);
        $categories = $this->catalogData->categories();
        $categoryContext = $this->catalogData->categoryContext($category);
        $componentContext = $this->catalogData->componentContext($componentSlug);
        $purposeContext = $this->catalogData->purposeContext($purposeSlug);
        $catalogPath = $this->catalogPath($categories, $category, $componentSlug, $purposeSlug);
        $categoryPath = $this->catalogPath($categories, $category, null, null);
        $copy = $this->pageCopy($categoryContext, $componentContext, $purposeContext);

        $productsTotal = $this->catalogData->countProducts($category, $query, $componentSlug, $purposeSlug);
        $totalPages = max(1, (int)ceil($productsTotal / self::PRODUCTS_PER_PAGE));
        $currentPage = min(max(1, $page ?? 1), $totalPages);
        $offset = ($currentPage - 1) * self::PRODUCTS_PER_PAGE;

        $products = $this->catalogData->products(
            selectedCategory: $category,
            limit: self::PRODUCTS_PER_PAGE,
            search: $query,
            sort: $sort,
            offset: $offset,
            componentSlug: $componentSlug,
            purposeSlug: $purposeSlug,
        );
        $componentFilters = $this->catalogData->componentFilters($category);
        $purposeFilters = $this->catalogData->purposeFilters($category);
        $isFacetWithoutCategory = $category === null && ($componentContext !== null || $purposeContext !== null);
        $hasUnknownContext = ($category !== null && $categoryContext === null)
            || (trim((string)$componentSlug) !== '' && $componentContext === null)
            || (trim((string)$purposeSlug) !== '' && $purposeContext === null);
        $isContextIndexable = ($categoryContext === null || $categoryContext['is_indexable'])
            && ($componentContext === null || $componentContext['is_indexable'])
            && ($purposeContext === null || $purposeContext['is_indexable']);
        $isFiltered = $query !== null
            || $sort !== 'default'
            || $view !== 'grid'
            || $currentPage > 1
            || $productsTotal === 0
            || $hasUnknownContext
            || !$isContextIndexable
            || $isFacetWithoutCategory;

        return new CatalogPageView(
            meta: $this->pages->applySystem('catalog', new PageMetaView(
                title: $copy['title'],
                description: $copy['description'],
                canonicalUrl: $this->urls->absolute($catalogPath),
                robots: $isFiltered ? 'noindex, follow' : 'index, follow',
                ogTitle: $copy['title'],
                ogDescription: $copy['description'],
                ogImage: $this->urls->absolute('/assets/images/og/default.jpg'),
                ogImageAlt: 'Каталог БИОФАРМ',
                jsonLd: [
                    $this->jsonLd->breadcrumbs([
                        ['name' => 'Главная', 'url' => '/'],
                        ['name' => 'Каталог', 'url' => $catalogPath],
                    ]),
                    $this->jsonLd->itemList($products, $catalogPath),
                ],
            )),
            products: $products,
            categories: $categories,
            categoriesTotal: array_sum(array_map(
                static fn (HomeCategoryView $category): int => $category->productsCount,
                $categories,
            )),
            catalogPath: $catalogPath,
            catalogEyebrow: $copy['eyebrow'],
            catalogH1: $copy['h1'],
            catalogLead: $copy['lead'],
            introText: $copy['introText'],
            bottomText: $copy['bottomText'],
            componentFilters: $componentFilters,
            purposeFilters: $purposeFilters,
            activeComponentSlug: $componentContext['slug'] ?? null,
            activePurposeSlug: $purposeContext['slug'] ?? null,
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
            pageUrls: $this->pageUrls($this->pageNumbers($currentPage, $totalPages), $catalogPath, $query, $sort, $view),
            previousPageUrl: $currentPage > 1 ? $this->catalogUrl($catalogPath, $query, $sort, $view, $currentPage - 1) : null,
            nextPageUrl: $currentPage < $totalPages ? $this->catalogUrl($catalogPath, $query, $sort, $view, $currentPage + 1) : null,
            allCategoryUrl: $this->catalogUrl('/catalog', $query, $sort, $view, 1),
            categoryUrls: $this->categoryUrls($categories, $query, $sort, $view),
            componentFilterUrls: $this->facetUrls($componentFilters, $categoryPath, 'sostav', $query, $sort, $view),
            purposeFilterUrls: $this->facetUrls($purposeFilters, $categoryPath, 'dlya', $query, $sort, $view),
            viewUrls: [
                'grid' => $this->catalogUrl($catalogPath, $query, $sort, 'grid', 1),
                'list' => $this->catalogUrl($catalogPath, $query, $sort, 'list', 1),
            ],
            resetUrl: '/catalog#catalog',
        );
    }

    /**
     * @param list<CatalogFacetView> $facets
     * @return array<string, string>
     */
    private function facetUrls(
        array $facets,
        string $categoryPath,
        string $segment,
        ?string $query,
        string $sort,
        string $view,
    ): array
    {
        $urls = [];
        foreach ($facets as $facet) {
            $urls[$facet->slug] = $this->catalogUrl($categoryPath . '/' . $segment . '/' . rawurlencode($facet->slug), $query, $sort, $view, 1);
        }

        return $urls;
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
    private function pageUrls(array $pages, string $path, ?string $query, string $sort, string $view): array
    {
        $urls = [];
        foreach ($pages as $page) {
            $urls[$page] = $this->catalogUrl($path, $query, $sort, $view, $page);
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
            $urls[$item->id] = $this->catalogUrl($this->categoryPath($item), $query, $sort, $view, 1);
        }

        return $urls;
    }

    private function catalogUrl(
        string $path,
        ?string $query,
        string $sort,
        string $view,
        int $page,
    ): string {
        $params = [
            'q'    => $query,
            'sort' => $sort !== 'default' ? $sort : null,
            'view' => $view !== 'grid' ? $view : null,
            'page' => $page > 1 ? $page : null,
        ];

        $params = array_filter(
            $params,
            static fn ($value): bool => $value !== null && $value !== '',
        );

        return $path . ($params !== [] ? '?' . http_build_query($params) : '') . '#catalog';
    }

    /**
     * @param list<HomeCategoryView> $categories
     */
    private function catalogPath(
        array $categories,
        ?string $categoryId,
        ?string $componentSlug,
        ?string $purposeSlug,
    ): string {
        $path = '/catalog';

        if ($categoryId !== null) {
            foreach ($categories as $category) {
                if ($category->id === $categoryId) {
                    $path = $this->categoryPath($category);
                    break;
                }
            }
        }

        $componentSlug = trim((string)$componentSlug);
        if ($componentSlug !== '') {
            return $path . '/sostav/' . rawurlencode($componentSlug);
        }

        $purposeSlug = trim((string)$purposeSlug);
        if ($purposeSlug !== '') {
            return $path . '/dlya/' . rawurlencode($purposeSlug);
        }

        return $path;
    }

    private function categoryPath(HomeCategoryView $category): string
    {
        if ($category->slug === null || $category->slug === '') {
            return '/catalog';
        }

        if ($category->parentSlug !== null && $category->parentSlug !== '') {
            return '/catalog/' . rawurlencode($category->parentSlug) . '/' . rawurlencode($category->slug);
        }

        return '/catalog/' . rawurlencode($category->slug);
    }

    /**
     * @param array{
     *     id: string,
     *     slug: string,
     *     parent_slug: string|null,
     *     name: string,
     *     h1: string|null,
     *     seo_title: string|null,
     *     seo_description: string|null,
     *     intro_text: string|null,
     *     bottom_text: string|null,
     *     is_indexable: bool
     * }|null $category
     * @param array{
     *     slug: string,
     *     name: string,
     *     short_description: string|null,
     *     seo_title: string|null,
     *     seo_description: string|null,
     *     intro_text: string|null,
     *     is_indexable: bool
     * }|null $component
     * @param array{
     *     slug: string,
     *     name: string,
     *     h1: string|null,
     *     seo_title: string|null,
     *     seo_description: string|null,
     *     intro_text: string|null,
     *     bottom_text: string|null,
     *     is_indexable: bool
     * }|null $purpose
     * @return array{
     *     eyebrow: string,
     *     h1: string,
     *     lead: string,
     *     title: string,
     *     description: string,
     *     introText: string|null,
     *     bottomText: string|null
     * }
     */
    private function pageCopy(?array $category, ?array $component, ?array $purpose): array
    {
        $categoryName = $category !== null
            ? ($category['h1'] ?? $category['name'])
            : null;

        if ($purpose !== null) {
            $h1 = $categoryName !== null
                ? $categoryName . ' ' . $purpose['name']
                : ($purpose['h1'] ?? 'БАДы ' . $purpose['name']);
            $description = $purpose['seo_description']
                ?? ('Натуральная продукция БИОФАРМ ' . $purpose['name'] . '.');

            return [
                'eyebrow'     => 'Для здоровья',
                'h1'          => $h1,
                'lead'        => $description,
                'title'       => $purpose['seo_title'] ?? ($h1 . ' — БИОФАРМ'),
                'description' => $description,
                'introText'   => $purpose['intro_text'] ?? $category['intro_text'] ?? null,
                'bottomText'  => $purpose['bottom_text'] ?? $category['bottom_text'] ?? null,
            ];
        }

        if ($component !== null) {
            $componentName = mb_strtolower($component['name']);
            $h1 = $categoryName !== null
                ? $categoryName . ' с ' . $componentName
                : 'БАДы с ' . $componentName;
            $description = $component['seo_description']
                ?? ('Каталог продукции БИОФАРМ с компонентом ' . $componentName . '.');

            return [
                'eyebrow'     => 'Состав',
                'h1'          => $h1,
                'lead'        => $description,
                'title'       => $component['seo_title'] ?? ($h1 . ' — БИОФАРМ'),
                'description' => $description,
                'introText'   => $component['intro_text'] ?? $component['short_description'] ?? $category['intro_text'] ?? null,
                'bottomText'  => $category['bottom_text'] ?? null,
            ];
        }

        if ($category !== null) {
            $h1 = $category['h1'] ?? $category['name'];
            $description = $category['seo_description']
                ?? ('Каталог продукции БИОФАРМ в категории ' . $category['name'] . '.');

            return [
                'eyebrow'     => 'Натуральные продукты',
                'h1'          => $h1,
                'lead'        => $description,
                'title'       => $category['seo_title'] ?? ($h1 . ' — БИОФАРМ'),
                'description' => $description,
                'introText'   => $category['intro_text'],
                'bottomText'  => $category['bottom_text'],
            ];
        }

        return [
            'eyebrow'     => 'Натуральные продукты',
            'h1'          => 'Каталог товаров',
            'lead'        => 'Экологически чистые продукты с собственных ферм. Без пестицидов, без ГМО — только природа.',
            'title'       => 'Каталог товаров — БИОФАРМ',
            'description' => 'Каталог натуральной продукции БИОФАРМ.',
            'introText'   => null,
            'bottomText'  => null,
        ];
    }
}
