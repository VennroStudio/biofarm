<?php

declare(strict_types=1);

namespace App\Http\Unifier\ProductPage;

use App\Components\Seo\JsonLdFactory;
use App\Components\Seo\SeoUrlGenerator;
use App\Components\Setting\SiteSettings;
use App\Http\Unifier\Faq\FaqDataProvider;
use App\Http\Unifier\Product\ProductCatalogDataProvider;
use App\Http\View\PageMetaView;
use App\Http\View\Product\ProductPageView;

final readonly class ProductPageUnifier
{
    public function __construct(
        private ProductCatalogDataProvider $products,
        private SeoUrlGenerator $urls,
        private JsonLdFactory $jsonLd,
        private FaqDataProvider $faq,
        private SiteSettings $settings,
    ) {}

    public function unify(string $slug): ProductPageView
    {
        $product = $this->products->pageProductBySlug($slug);
        $faqItems = $product !== null ? $this->faq->forPage('product', $product->slug) : [];
        $title = $product?->seoTitle ?: (
            $product !== null
                ? $this->template(
                    'seo_product_title_template',
                    [
                        'category' => $product->category,
                        'h1'       => $product->h1 ?: $product->title,
                        'name'     => $product->title,
                        'price'    => (string)(int)$product->price,
                        'slug'     => $product->slug,
                        'weight'   => $product->weight,
                    ],
                    $product->title . ' — БИОФАРМ',
                )
                : 'Товар — БИОФАРМ'
        );
        $description = $this->metaDescription($product?->seoDescription ?: (
            $product !== null
                ? $this->template(
                    'seo_product_description_template',
                    [
                        'category' => $product->category,
                        'h1'       => $product->h1 ?: $product->title,
                        'name'     => $product->title,
                        'price'    => (string)(int)$product->price,
                        'slug'     => $product->slug,
                        'weight'   => $product->weight,
                    ],
                    $product->shortDescription ?: $product->description ?: 'Карточка товара БИОФАРМ.',
                )
                : 'Карточка товара БИОФАРМ.'
        ));
        $canonicalUrl = $this->urls->absolute('/product/' . trim($slug));
        $jsonLd = [];
        if ($product !== null) {
            $jsonLd = [
                $this->jsonLd->breadcrumbs([
                    ['name' => 'Главная', 'url' => '/'],
                    ['name' => 'Каталог', 'url' => '/catalog'],
                    ['name' => $product->title, 'url' => '/product/' . $product->slug],
                ]),
                $this->jsonLd->product($product),
            ];

            if ($faqItems !== []) {
                $jsonLd[] = $this->jsonLd->faqPage($faqItems);
            }
        }

        return new ProductPageView(
            meta: new PageMetaView(
                title: $title,
                description: $description,
                canonicalUrl: $canonicalUrl,
                robots: $product !== null ? 'index, follow' : 'noindex, follow',
                ogTitle: $product?->h1 ?: $product?->title ?: $title,
                ogDescription: $description,
                ogImage: $product !== null ? $this->urls->absolute($product->image) : null,
                ogImageAlt: $product?->imageAlt ?: $product?->title ?: 'Товар БИОФАРМ',
                ogType: 'product',
                jsonLd: $jsonLd,
            ),
            product: $product,
            relatedProducts: $product !== null
                ? $this->products->relatedProducts($product->categoryId, $product->id)
                : [],
            certificates: $product !== null
                ? $this->products->certificatesForProduct($product->id)
                : [],
            relatedPosts: $product !== null
                ? $this->products->relatedBlogPosts($product->id)
                : [],
            faqItems: $faqItems,
        );
    }

    private function metaDescription(string $value): string
    {
        $value = trim((string)preg_replace('/\s+/u', ' ', strip_tags($value)));

        return mb_strlen($value) > 220 ? mb_substr($value, 0, 217) . '...' : $value;
    }

    /**
     * @param array<string, string> $variables
     */
    private function template(string $key, array $variables, string $fallback): string
    {
        $template = trim((string)$this->settings->get($key, $fallback));
        if ($template === '') {
            $template = $fallback;
        }

        $replacements = [];
        foreach ($variables as $name => $value) {
            $replacements['{' . $name . '}'] = $value;
        }

        return strtr($template, $replacements);
    }
}
