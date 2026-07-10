<?php

declare(strict_types=1);

namespace App\Http\View\Home;

use App\Http\View\Blog\BlogPostView;
use App\Http\View\PageMetaView;
use App\Http\View\Product\ProductCardView;

final readonly class HomePageView
{
    /**
     * @param list<ProductCardView> $products
     * @param list<HomeCategoryView> $categories
     * @param list<BlogPostView> $blogPosts
     * @param list<HomeReviewView> $reviews
     */
    public function __construct(
        public PageMetaView $meta,
        public array $products,
        public ?string $selectedCategory,
        public ?ProductCardView $featuredProduct,
        public array $categories,
        public int $categoriesTotal,
        public array $blogPosts,
        public array $reviews,
    ) {}
}
