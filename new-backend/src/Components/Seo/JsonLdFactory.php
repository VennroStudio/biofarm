<?php

declare(strict_types=1);

namespace App\Components\Seo;

use App\Components\Setting\SiteSettings;
use App\Http\View\Blog\BlogPostView;
use App\Http\View\Product\ProductCardView;
use App\Http\View\Product\ProductPageProductView;
use DateTimeImmutable;
use Throwable;

final readonly class JsonLdFactory
{
    public function __construct(
        private SeoUrlGenerator $urls,
        private SiteSettings $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function organization(): array
    {
        $name = $this->stringSetting('site_name', 'БИОФАРМ');
        $phone = $this->stringSetting('site_phone', '+7 (999) 123-45-67');
        $email = $this->stringSetting('site_email', 'bio.active@bk.ru');

        return array_filter([
            '@context'     => 'https://schema.org',
            '@type'        => 'Organization',
            'name'         => $name,
            'url'          => $this->urls->baseUrl(),
            'logo'         => $this->urls->absolute($this->stringSetting('site_logo_url', '/uploads/images/logo.png')),
            'telephone'    => $phone,
            'email'        => $email,
            'address'      => [
                '@type'           => 'PostalAddress',
                'addressCountry'  => $this->stringSetting('site_address_country', 'RU'),
                'addressRegion'   => $this->stringSetting('site_address_region', 'Томская область'),
                'addressLocality' => $this->stringSetting('site_address_locality', 'Томск'),
                'streetAddress'   => $this->stringSetting('site_address_street', 'особая экономическая зона микрорайон Академгородок, проспект Развитие 3Е'),
            ],
            'contactPoint' => [
                '@type'             => 'ContactPoint',
                'telephone'         => $phone,
                'contactType'       => 'customer support',
                'areaServed'        => 'RU',
                'availableLanguage' => 'Russian',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function website(): array
    {
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => $this->stringSetting('site_name', 'БИОФАРМ'),
            'url'             => $this->urls->baseUrl(),
            'inLanguage'      => 'ru-RU',
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => $this->urls->absolute('/catalog?q={search_term_string}'),
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function webPage(string $name, string $description, string $url): array
    {
        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebPage',
            'name'        => $name,
            'description' => $description,
            'url'         => $this->urls->absolute($url),
            'inLanguage'  => 'ru-RU',
            'isPartOf'    => [
                '@type' => 'WebSite',
                'name'  => $this->stringSetting('site_name', 'БИОФАРМ'),
                'url'   => $this->urls->baseUrl(),
            ],
        ];
    }

    /**
     * @param list<array{name: string, url: string}> $items
     * @return array<string, mixed>
     */
    public function breadcrumbs(array $items): array
    {
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => array_map(
                fn (array $item, int $index): array => [
                    '@type'    => 'ListItem',
                    'position' => $index + 1,
                    'name'     => $item['name'],
                    'item'     => $this->urls->absolute($item['url']),
                ],
                $items,
                array_keys($items),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function product(ProductPageProductView $product): array
    {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $product->title,
            'image'       => array_map($this->urls->absolute(...), $product->images),
            'description' => $product->shortDescription ?: $product->description,
            'brand'       => [
                '@type' => 'Brand',
                'name'  => $this->stringSetting('site_name', 'БИОФАРМ'),
            ],
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => $this->urls->absolute('/product/' . $product->slug),
                'priceCurrency' => 'RUB',
                'price'         => (string)(int)$product->price,
                'availability'  => $this->schemaAvailability($product->availability),
            ],
        ];

        if ($product->sku !== null) {
            $schema['sku'] = $product->sku;
        }

        if ($product->gtin !== null) {
            $schema['gtin'] = $product->gtin;
        }

        if ($product->ratingCount > 0) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => $product->ratingRate,
                'reviewCount' => $product->ratingCount,
            ];
        }

        return $schema;
    }

    /**
     * @param list<ProductCardView> $products
     * @return array<string, mixed>
     */
    public function itemList(array $products, string $url): array
    {
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'url'             => $this->urls->absolute($url),
            'itemListElement' => array_map(
                fn (ProductCardView $product, int $index): array => [
                    '@type'    => 'ListItem',
                    'position' => $index + 1,
                    'url'      => $this->urls->absolute('/product/' . $product->slug),
                    'name'     => $product->title,
                ],
                $products,
                array_keys($products),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function blogPosting(BlogPostView $post): array
    {
        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => 'BlogPosting',
            'headline'         => $post->title,
            'description'      => $post->excerpt,
            'image'            => $this->urls->absolute($post->image),
            'url'              => $this->urls->absolute('/blog/' . $post->slug),
            'author'           => [
                '@type' => 'Person',
                'name'  => $post->authorName,
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => $this->stringSetting('site_name', 'БИОФАРМ'),
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => $this->urls->absolute($this->stringSetting('site_logo_url', '/uploads/images/logo.png')),
                ],
            ],
            'mainEntityOfPage' => $this->urls->absolute('/blog/' . $post->slug),
            'inLanguage'       => 'ru-RU',
        ];

        $publishedAt = $this->date($post->publishedAt);
        if ($publishedAt !== null) {
            $schema['datePublished'] = $publishedAt;
        }

        return $schema;
    }

    private function date(?string $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($date))->format(DATE_ATOM);
        } catch (Throwable) {
            return null;
        }
    }

    private function schemaAvailability(string $availability): string
    {
        return match ($availability) {
            'out_of_stock' => 'https://schema.org/OutOfStock',
            'preorder'     => 'https://schema.org/PreOrder',
            default        => 'https://schema.org/InStock',
        };
    }

    private function stringSetting(string $key, string $default): string
    {
        $value = $this->settings->get($key, $default);
        if (!\is_scalar($value)) {
            return $default;
        }

        $value = trim((string)$value);

        return $value === '' ? $default : $value;
    }
}
