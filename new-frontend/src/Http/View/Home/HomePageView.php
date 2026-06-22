<?php

declare(strict_types=1);

namespace App\Http\View\Home;

use App\Http\View\MetricView;
use App\Http\View\PageMetaView;
use App\Modules\Order\Api\Response\OrderResponse;
use App\Modules\Product\Api\Response\ProductResponse;
use App\Modules\Review\Api\Response\ReviewResponse;

final readonly class HomePageView
{
    /**
     * @param list<ProductResponse> $products
     * @param list<ReviewResponse> $reviews
     * @param list<OrderResponse> $orders
     * @param list<HomeCategoryView> $categories
     * @param list<MetricView> $metrics
     */
    public function __construct(
        public PageMetaView $meta,
        public array $products,
        public ?string $selectedCategory,
        public ?ProductResponse $featuredProduct,
        public array $reviews,
        public array $orders,
        public array $categories,
        public int $categoriesTotal,
        public array $metrics,
        public ?string $apiError,
    ) {}
}
