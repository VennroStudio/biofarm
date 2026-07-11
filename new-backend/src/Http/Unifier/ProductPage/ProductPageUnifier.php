<?php

declare(strict_types=1);

namespace App\Http\Unifier\ProductPage;

use App\Http\Unifier\Product\ProductCatalogDataProvider;
use App\Http\View\PageMetaView;
use App\Http\View\Product\ProductPageView;

final readonly class ProductPageUnifier
{
    public function __construct(
        private ProductCatalogDataProvider $products,
    ) {}

    public function unify(string $slug): ProductPageView
    {
        $product = $this->products->pageProductBySlug($slug);

        return new ProductPageView(
            meta: new PageMetaView(
                title: $product !== null ? $product->title . ' — БИОФАРМ' : 'Товар — БИОФАРМ',
                description: $product?->shortDescription ?? $product?->description ?? 'Карточка товара БИОФАРМ.',
            ),
            product: $product,
            relatedProducts: $product !== null
                ? $this->products->relatedProducts($product->categoryId, $product->id)
                : [],
        );
    }
}
