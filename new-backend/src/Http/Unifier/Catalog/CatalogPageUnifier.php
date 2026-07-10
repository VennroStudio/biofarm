<?php

declare(strict_types=1);

namespace App\Http\Unifier\Catalog;

use App\Http\View\Catalog\CatalogPageView;
use App\Http\View\PageMetaView;

final readonly class CatalogPageUnifier
{
    public function unify(?string $selectedCategory = null): CatalogPageView
    {
        return new CatalogPageView(
            meta: new PageMetaView(
                title: 'Каталог — БИОФАРМ',
                description: 'Каталог натуральной продукции БИОФАРМ.',
            ),
            products: [],
            categories: [],
            categoriesTotal: 0,
            selectedCategory: $selectedCategory,
        );
    }
}
