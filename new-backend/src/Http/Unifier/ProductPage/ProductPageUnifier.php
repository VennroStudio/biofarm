<?php

declare(strict_types=1);

namespace App\Http\Unifier\ProductPage;

use App\Http\View\PageMetaView;
use App\Http\View\Product\ProductPageView;

final readonly class ProductPageUnifier
{
    public function unify(string $slug): ProductPageView
    {
        unset($slug);

        return new ProductPageView(
            meta: new PageMetaView(
                title: 'Товар — БИОФАРМ',
                description: 'Карточка товара БИОФАРМ.',
            ),
            product: null,
            reviews: [],
        );
    }
}
