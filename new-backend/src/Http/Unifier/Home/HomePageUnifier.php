<?php

declare(strict_types=1);

namespace App\Http\Unifier\Home;

use App\Http\View\Home\HomeCategoryView;
use App\Http\View\Home\HomePageView;
use App\Http\View\MetricView;
use App\Http\View\PageMetaView;
use App\Http\View\Product\ProductCardView;

final readonly class HomePageUnifier
{
    public function unify(?string $selectedCategory = null): HomePageView
    {
        $products = $this->products($selectedCategory);
        $categories = $this->categories($products);

        return new HomePageView(
            meta: new PageMetaView(
                title: 'БИОФАРМ — натуральные продукты',
                description: 'Экологически чистые продукты БИОФАРМ напрямую из собственных лабораторий.',
            ),
            products: $products,
            selectedCategory: $selectedCategory,
            featuredProduct: $products[0] ?? null,
            reviews: [],
            orders: [],
            categories: $categories,
            categoriesTotal: array_sum(array_map(
                static fn (HomeCategoryView $category): int => $category->productsCount,
                $categories,
            )),
            metrics: $this->metrics($products, $categories),
        );
    }

    /**
     * @return list<ProductCardView>
     */
    private function products(?string $selectedCategory): array
    {
        unset($selectedCategory);

        return [];
    }

    /**
     * @param list<ProductCardView> $products
     * @return list<HomeCategoryView>
     */
    private function categories(array $products): array
    {
        $counts = [];
        foreach ($products as $product) {
            $counts[$product->category] = ($counts[$product->category] ?? 0) + 1;
        }

        ksort($counts);

        $categories = [];
        foreach ($counts as $name => $count) {
            $categories[] = new HomeCategoryView($name, $count);
        }

        return $categories;
    }

    /**
     * @param list<ProductCardView> $products
     * @param list<HomeCategoryView> $categories
     * @return list<MetricView>
     */
    private function metrics(array $products, array $categories): array
    {
        if ($products === []) {
            return [];
        }

        return [
            new MetricView('Товары', (string)\count($products), 'загружены из базы сайта'),
            new MetricView('Категории', (string)\count($categories), 'сгруппированы для каталога'),
        ];
    }
}
