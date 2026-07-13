<?php

declare(strict_types=1);

namespace App\Http\Unifier\ProductPage;

use App\Components\Seo\JsonLdFactory;
use App\Components\Seo\SeoUrlGenerator;
use App\Http\Unifier\Product\ProductCatalogDataProvider;
use App\Http\View\PageMetaView;
use App\Http\View\Product\ProductPageView;

final readonly class ProductPageUnifier
{
    public function __construct(
        private ProductCatalogDataProvider $products,
        private SeoUrlGenerator $urls,
        private JsonLdFactory $jsonLd,
    ) {}

    public function unify(string $slug): ProductPageView
    {
        $product = $this->products->pageProductBySlug($slug);
        $title = $product?->seoTitle ?: ($product !== null ? $product->title . ' — БИОФАРМ' : 'Товар — БИОФАРМ');
        $description = $this->metaDescription(
            $product?->seoDescription ?: $product?->shortDescription ?: $product?->description ?: 'Карточка товара БИОФАРМ.',
        );
        $canonicalUrl = $this->urls->absolute('/product/' . trim($slug));

        return new ProductPageView(
            meta: new PageMetaView(
                title: $title,
                description: $description,
                canonicalUrl: $canonicalUrl,
                robots: $product !== null ? 'index, follow' : 'noindex, follow',
                ogTitle: $product?->h1 ?: $product?->title ?: $title,
                ogDescription: $description,
                ogImage: $product !== null ? $this->urls->absolute($product->image) : $this->urls->absolute('/assets/images/og/default.jpg'),
                ogImageAlt: $product?->imageAlt ?: $product?->title ?: 'Товар БИОФАРМ',
                ogType: 'product',
                jsonLd: $product !== null ? [
                    $this->jsonLd->breadcrumbs([
                        ['name' => 'Главная', 'url' => '/'],
                        ['name' => 'Каталог', 'url' => '/catalog'],
                        ['name' => $product->title, 'url' => '/product/' . $product->slug],
                    ]),
                    $this->jsonLd->product($product),
                ] : [],
            ),
            product: $product,
            relatedProducts: $product !== null
                ? $this->products->relatedProducts($product->categoryId, $product->id)
                : [],
        );
    }

    private function metaDescription(string $value): string
    {
        $value = trim((string)preg_replace('/\s+/u', ' ', strip_tags($value)));

        return mb_strlen($value) > 220 ? mb_substr($value, 0, 217) . '...' : $value;
    }
}
