<?php

declare(strict_types=1);

namespace App\Http\Unifier\Home;

use App\Components\Api\ApiException;
use App\Http\View\Home\HomeCategoryView;
use App\Http\View\Home\HomePageView;
use App\Http\View\MetricView;
use App\Http\View\PageMetaView;
use App\Modules\Order\Api\OrderApi;
use App\Modules\Order\Api\Response\OrderResponse;
use App\Modules\Product\Api\ProductApi;
use App\Modules\Product\Api\Response\ProductResponse;
use App\Modules\Review\Api\ReviewApi;

final readonly class HomePageUnifier
{
    public function __construct(
        private ProductApi $products,
        private ReviewApi $reviews,
        private OrderApi $orders,
    ) {}

    public function unify(?string $selectedCategory = null): HomePageView
    {
        $meta = $this->meta();
        $apiErrors = [];
        $products = [];
        $reviews = [];
        $orders = [];

        try {
            $products = $this->products->getProducts(limit: 8);
        } catch (ApiException $exception) {
            $apiErrors[] = 'Products: ' . $exception->getMessage();
        }

        $catalogProducts = $products;
        if ($selectedCategory !== null) {
            try {
                $catalogProducts = $this->products->getProductsByCategory($selectedCategory, limit: 8);
            } catch (ApiException $exception) {
                $catalogProducts = [];
                $apiErrors[] = "Products category '{$selectedCategory}': " . $exception->getMessage();
            }
        }

        $featuredProduct = $catalogProducts[0] ?? null;
        if ($featuredProduct === null) {
            $featuredProduct = $products[0] ?? null;
        }

        if ($featuredProduct !== null) {
            try {
                $reviews = $this->reviews->getProductReviews(productId: $featuredProduct->id, limit: 3);
            } catch (ApiException $exception) {
                $apiErrors[] = 'Reviews: ' . $exception->getMessage();
            }
        }

        try {
            $orders = $this->orders->getOrders(limit: 4);
        } catch (ApiException $exception) {
            $apiErrors[] = 'Orders: ' . $exception->getMessage();
        }

        $categories = $this->mapCategories($products);
        $categoriesTotal = array_sum(array_map(
            static fn (HomeCategoryView $category): int => $category->productsCount,
            $categories,
        ));

        return new HomePageView(
            meta: $meta,
            products: $catalogProducts,
            selectedCategory: $selectedCategory,
            featuredProduct: $featuredProduct,
            reviews: $reviews,
            orders: $orders,
            categories: $categories,
            categoriesTotal: $categoriesTotal,
            metrics: $this->mapMetrics($catalogProducts, $orders, $categories),
            apiError: $apiErrors === [] ? null : implode(' ', $apiErrors),
        );
    }

    private function meta(): PageMetaView
    {
        return new PageMetaView(
            title: 'Slim Frontend Template',
            description: 'Тестовая Slim/Twig страница, собранная из внешнего Fake E-commerce API.',
        );
    }

    /**
     * @param list<ProductResponse> $products
     * @return list<HomeCategoryView>
     */
    private function mapCategories(array $products): array
    {
        /** @var array<string, int> $counts */
        $counts = [];

        foreach ($products as $product) {
            $counts[$product->category] = ($counts[$product->category] ?? 0) + 1;
        }

        ksort($counts);

        $categories = [];
        foreach ($counts as $name => $count) {
            $categories[] = new HomeCategoryView(
                name: $name,
                productsCount: $count,
            );
        }

        return $categories;
    }

    /**
     * @param list<ProductResponse> $products
     * @param list<OrderResponse> $orders
     * @param list<HomeCategoryView> $categories
     * @return list<MetricView>
     */
    private function mapMetrics(array $products, array $orders, array $categories): array
    {
        if ($products === []) {
            return [];
        }

        $averageRating = array_sum(array_map(
            static fn (ProductResponse $product): float => $product->ratingRate,
            $products,
        )) / \count($products);

        return [
            new MetricView(
                label: 'Products',
                value: (string)\count($products),
                description: 'items loaded from Fake API',
            ),
            new MetricView(
                label: 'Categories',
                value: (string)\count($categories),
                description: 'grouped for the page',
            ),
            new MetricView(
                label: 'Avg rating',
                value: number_format($averageRating, 1),
                description: 'calculated from API models',
            ),
            new MetricView(
                label: 'Orders',
                value: (string)\count($orders),
                description: 'loaded from the Order module',
            ),
        ];
    }
}
