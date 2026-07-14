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
        bool $useFacetSeo = false,
    ): CatalogPageView {
        $category = $this->catalogData->categoryId($selectedCategory);
        if (
            $category !== null
            && !ctype_digit($category)
            && $componentSlug === null
            && $purposeSlug === null
        ) {
            $facetRoute = $this->catalogData->facetRouteByValueSlug($category);
            if ($facetRoute !== null) {
                $category = null;
                $selectedCategory = null;
                $useFacetSeo = true;

                if ($facetRoute['filter_prefix'] === 'sostav') {
                    $componentSlug = $facetRoute['slug'];
                }

                if ($facetRoute['filter_prefix'] === 'dlya') {
                    $purposeSlug = $facetRoute['slug'];
                }
            }
        }

        $query = $this->normalizeSearch($searchQuery);
        $sort = $this->normalizeSort($sortBy);
        $view = $this->normalizeView($viewMode);
        $categories = $this->catalogData->categories();
        $categoryContext = $this->catalogData->categoryContext($category);
        $componentContext = $this->catalogData->componentContext($componentSlug);
        $purposeContext = $this->catalogData->purposeContext($purposeSlug);
        $activeComponentSlug = $componentContext['slug'] ?? null;
        $activePurposeSlug = $purposeContext['slug'] ?? null;
        $catalogPath = $this->catalogPath(
            $categories,
            $category,
            $useFacetSeo ? $activeComponentSlug : null,
            $useFacetSeo ? $activePurposeSlug : null,
        );
        $categoryPath = $this->catalogPath($categories, $category, null, null);
        $copy = $this->pageCopy(
            $categoryContext,
            $useFacetSeo ? $componentContext : null,
            $useFacetSeo ? $purposeContext : null,
        );

        $productsTotal = $this->catalogData->countProducts($category, $query, $componentSlug, $purposeSlug);
        $categoriesTotal = $this->catalogData->countProducts();
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
        $componentFilters = $this->catalogData->componentFilters($category, $query, $activePurposeSlug);
        $purposeFilters = $this->catalogData->purposeFilters($category, $query, $activeComponentSlug);
        $hasQueryFacet = !$useFacetSeo && ($this->hasSlug($componentSlug) || $this->hasSlug($purposeSlug));
        $hasMultipleFacets = $this->hasSlug($componentSlug) && $this->hasSlug($purposeSlug);
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
            || $hasQueryFacet
            || $hasMultipleFacets;
        $queryComponentSlug = $useFacetSeo ? null : $activeComponentSlug;
        $queryPurposeSlug = $useFacetSeo ? null : $activePurposeSlug;

        $meta = new PageMetaView(
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
            );

        if ($category === null && !$useFacetSeo) {
            $meta = $this->pages->applySystem('catalog', $meta);
        }

        return new CatalogPageView(
            meta: $meta,
            products: $products,
            categories: $categories,
            categoriesTotal: $categoriesTotal,
            catalogPath: $catalogPath,
            catalogEyebrow: $copy['eyebrow'],
            catalogH1: $copy['h1'],
            catalogLead: $copy['lead'],
            introText: $copy['introText'],
            bottomText: $copy['bottomText'],
            componentFilters: $componentFilters,
            purposeFilters: $purposeFilters,
            activeComponentSlug: $activeComponentSlug,
            activePurposeSlug: $activePurposeSlug,
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
            pageUrls: $this->pageUrls($this->pageNumbers($currentPage, $totalPages), $catalogPath, $query, $sort, $view, $queryComponentSlug, $queryPurposeSlug),
            previousPageUrl: $currentPage > 1 ? $this->catalogUrl($catalogPath, $query, $sort, $view, $currentPage - 1, $queryComponentSlug, $queryPurposeSlug) : null,
            nextPageUrl: $currentPage < $totalPages ? $this->catalogUrl($catalogPath, $query, $sort, $view, $currentPage + 1, $queryComponentSlug, $queryPurposeSlug) : null,
            allCategoryUrl: $this->catalogUrl('/catalog', $query, $sort, $view, 1),
            categoryUrls: $this->categoryUrls($categories, $query, $sort, $view),
            componentFilterUrls: $this->filterUrls($componentFilters, $categoryPath, $query, $sort, $view, $activeComponentSlug, $activePurposeSlug, 'sostav'),
            purposeFilterUrls: $this->filterUrls($purposeFilters, $categoryPath, $query, $sort, $view, $activeComponentSlug, $activePurposeSlug, 'dlya'),
            viewUrls: [
                'grid' => $this->catalogUrl($catalogPath, $query, $sort, 'grid', 1, $queryComponentSlug, $queryPurposeSlug),
                'list' => $this->catalogUrl($catalogPath, $query, $sort, 'list', 1, $queryComponentSlug, $queryPurposeSlug),
            ],
            resetUrl: '/catalog#catalog',
        );
    }

    /**
     * @param list<CatalogFacetView> $facets
     * @return array<string, string>
     */
    private function filterUrls(
        array $facets,
        string $categoryPath,
        ?string $query,
        string $sort,
        string $view,
        ?string $activeComponentSlug,
        ?string $activePurposeSlug,
        string $filterKey,
    ): array
    {
        $urls = [];
        foreach ($facets as $facet) {
            $componentSlug = $activeComponentSlug;
            $purposeSlug = $activePurposeSlug;

            if ($filterKey === 'sostav') {
                $componentSlug = $activeComponentSlug === $facet->slug ? null : $facet->slug;
            } else {
                $purposeSlug = $activePurposeSlug === $facet->slug ? null : $facet->slug;
            }

            $urls[$facet->slug] = $this->catalogUrl($categoryPath, $query, $sort, $view, 1, $componentSlug, $purposeSlug);
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
    private function pageUrls(
        array $pages,
        string $path,
        ?string $query,
        string $sort,
        string $view,
        ?string $componentSlug,
        ?string $purposeSlug,
    ): array
    {
        $urls = [];
        foreach ($pages as $page) {
            $urls[$page] = $this->catalogUrl($path, $query, $sort, $view, $page, $componentSlug, $purposeSlug);
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
        ?string $componentSlug = null,
        ?string $purposeSlug = null,
    ): string {
        $params = [
            'q'      => $query,
            'sostav' => $componentSlug,
            'dlya'   => $purposeSlug,
            'sort'   => $sort !== 'default' ? $sort : null,
            'view'   => $view !== 'grid' ? $view : null,
            'page'   => $page > 1 ? $page : null,
        ];

        $params = array_filter(
            $params,
            static fn ($value): bool => $value !== null && $value !== '',
        );

        return $path . ($params !== [] ? '?' . http_build_query($params) : '') . '#catalog';
    }

    private function hasSlug(?string $slug): bool
    {
        return trim((string)$slug) !== '';
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
     *     h1: string|null,
     *     short_description: string|null,
     *     seo_title: string|null,
     *     seo_description: string|null,
     *     intro_text: string|null,
     *     bottom_text: string|null,
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
        $categoryHeading = $category !== null
            ? ($category['h1'] ?? $category['name'])
            : null;
        $categoryName = $category['name'] ?? null;

        if ($purpose !== null) {
            $purposePhrase = $this->facetPhrase($purpose['h1'], $this->lowerFirst($purpose['name']));
            $h1 = $categoryName !== null
                ? $categoryName . ' ' . $purposePhrase
                : ($purpose['h1'] ?? 'БАДы ' . $purpose['name']);
            $description = $categoryName !== null
                ? ('Подборка товаров БИОФАРМ в категории «' . $categoryName . '» ' . $purposePhrase . '. Натуральные растительные экстракты и БАДы с доставкой по России.')
                : ($purpose['seo_description'] ?? ('Натуральная продукция БИОФАРМ ' . $purpose['name'] . '.'));

            return [
                'eyebrow'     => 'Для здоровья',
                'h1'          => $h1,
                'lead'        => $description,
                'title'       => $categoryName !== null ? ($h1 . ' — БИОФАРМ') : ($purpose['seo_title'] ?? ($h1 . ' — БИОФАРМ')),
                'description' => $description,
                'introText'   => $purpose['intro_text'] ?? $category['intro_text'] ?? null,
                'bottomText'  => $purpose['bottom_text'] ?? $category['bottom_text'] ?? null,
            ];
        }

        if ($component !== null) {
            $componentName = mb_strtolower($component['name']);
            $componentPhrase = $this->facetPhrase($component['h1'], 'с компонентом ' . $componentName);
            $h1 = $categoryName !== null
                ? $categoryName . ' ' . $componentPhrase
                : ($component['h1'] ?? 'БАДы с ' . $componentName);
            $description = $categoryName !== null
                ? ('Подборка товаров БИОФАРМ в категории «' . $categoryName . '» ' . $componentPhrase . '. Натуральная продукция с понятным составом.')
                : ($component['seo_description'] ?? ('Каталог продукции БИОФАРМ с компонентом ' . $componentName . '.'));

            return [
                'eyebrow'     => 'Состав',
                'h1'          => $h1,
                'lead'        => $description,
                'title'       => $categoryName !== null ? ($h1 . ' — БИОФАРМ') : ($component['seo_title'] ?? ($h1 . ' — БИОФАРМ')),
                'description' => $description,
                'introText'   => $component['intro_text'] ?? $component['short_description'] ?? $category['intro_text'] ?? null,
                'bottomText'  => $component['bottom_text'] ?? $category['bottom_text'] ?? null,
            ];
        }

        if ($category !== null) {
            $h1 = $categoryHeading ?? $category['name'];
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

    private function facetPhrase(?string $h1, string $fallback): string
    {
        $heading = trim((string)$h1);
        if ($heading === '') {
            return $fallback;
        }

        $lowerHeading = mb_strtolower($heading);
        foreach (['бады ', 'товары '] as $prefix) {
            if (str_starts_with($lowerHeading, $prefix)) {
                return trim(mb_substr($heading, mb_strlen($prefix)));
            }
        }

        return $fallback;
    }

    private function lowerFirst(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return $value;
        }

        return mb_strtolower(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }
}
