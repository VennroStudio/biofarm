<?php

declare(strict_types=1);

namespace App\Http\View\Product;

use App\Http\View\PageMetaView;

final readonly class ProductPageView
{
    /**
     * @param list<ProductCardView> $relatedProducts
     */
    public function __construct(
        public PageMetaView $meta,
        public ?ProductPageProductView $product,
        public array $relatedProducts = [],
    ) {}
}
