<?php

declare(strict_types=1);

namespace App\Http\View\Home;

use App\Http\View\MetricView;
use App\Http\View\PageMetaView;
use App\Http\View\Order\OrderCardView;
use App\Http\View\Product\ProductCardView;
use App\Http\View\Review\ReviewCardView;

final readonly class HomePageView
{
    /**
     * @param list<ProductCardView> $products
     * @param list<ReviewCardView> $reviews
     * @param list<OrderCardView> $orders
     * @param list<HomeCategoryView> $categories
     * @param list<MetricView> $metrics
     */
    public function __construct(
        public PageMetaView $meta,
        public array $products,
        public ?string $selectedCategory,
        public ?ProductCardView $featuredProduct,
        public array $reviews,
        public array $orders,
        public array $categories,
        public int $categoriesTotal,
        public array $metrics,
    ) {}
}
