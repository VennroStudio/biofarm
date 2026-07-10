<?php

declare(strict_types=1);

namespace App\Http\View\Product;

use App\Http\View\PageMetaView;
use App\Http\View\Review\ReviewCardView;

final readonly class ProductPageView
{
    /**
     * @param list<ReviewCardView> $reviews
     */
    public function __construct(
        public PageMetaView $meta,
        public ?ProductCardView $product,
        public array $reviews,
    ) {}
}
