<?php

declare(strict_types=1);

namespace App\Http\Unifier\Home;

use App\Components\Api\ApiException;
use App\Modules\Order\Api\OrderApi;
use App\Modules\Order\Api\Response\OrderResponse;
use App\Modules\Product\Api\ProductApi;
use App\Modules\Product\Api\Response\ProductResponse;
use App\Modules\Review\Api\Response\ReviewResponse;
use App\Modules\Review\Api\ReviewApi;

final readonly class HomePageUnifier
{
    public function __construct(
        private ProductApi $products,
        private ReviewApi $reviews,
        private OrderApi $orders,
    ) {}

    /**
     * @return array{
     *     meta: array{title: string, description: string},
     *     products: list<ProductResponse>,
     *     selectedCategory: string|null,
     *     featuredProduct: ProductResponse|null,
     *     reviews: list<ReviewResponse>,
     *     orders: list<OrderResponse>,
     *     categories: list<array{name: string, productsCount: int}>,
     *     categoriesTotal: int,
     *     metrics: list<array{label: string, value: string, description: string}>,
     *     apiError: string|null
     * }
     */
    public function unify(?string $selectedCategory = null): array
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

        return [
            'meta'             => $meta,
            'products'         => $catalogProducts,
            'selectedCategory' => $selectedCategory,
            'featuredProduct'  => $featuredProduct,
            'reviews'          => $reviews,
            'orders'           => $orders,
            'categories'       => $categories,
            'categoriesTotal'  => array_sum(array_map(
                static fn (array $category): int => $category['productsCount'],
                $categories,
            )),
            'metrics'  => $this->mapMetrics($catalogProducts, $orders, $categories),
            'apiError' => $apiErrors === [] ? null : implode(' ', $apiErrors),
        ];
    }

    /**
     * @return array{title: string, description: string}
     */
    private function meta(): array
    {
        return [
            'title'       => 'Slim Frontend Template',
            'description' => 'Тестовая Slim/Twig страница, собранная из внешнего Fake E-commerce API.',
        ];
    }

    /**
     * @param list<ProductResponse> $products
     * @return list<array{name: string, productsCount: int}>
     */
    private function mapCategories(array $products): array
    {
        $counts = [];

        foreach ($products as $product) {
            $counts[$product->category] = ($counts[$product->category] ?? 0) + 1;
        }

        ksort($counts);

        return array_map(
            static fn (string $name, int $count): array => [
                'name'          => $name,
                'productsCount' => $count,
            ],
            array_keys($counts),
            array_values($counts),
        );
    }

    /**
     * @param list<ProductResponse> $products
     * @param list<OrderResponse> $orders
     * @param list<array{name: string, productsCount: int}> $categories
     * @return list<array{label: string, value: string, description: string}>
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
            [
                'label'       => 'Products',
                'value'       => (string)\count($products),
                'description' => 'items loaded from Fake API',
            ],
            [
                'label'       => 'Categories',
                'value'       => (string)\count($categories),
                'description' => 'grouped for the page',
            ],
            [
                'label'       => 'Avg rating',
                'value'       => number_format($averageRating, 1),
                'description' => 'calculated from API models',
            ],
            [
                'label'       => 'Orders',
                'value'       => (string)\count($orders),
                'description' => 'loaded from the Order module',
            ],
        ];
    }
}
