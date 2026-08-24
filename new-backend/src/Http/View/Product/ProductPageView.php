<?php

declare(strict_types=1);

namespace App\Http\View\Product;

use App\Http\View\Blog\BlogPostView;
use App\Http\View\Faq\FaqItemView;
use App\Http\View\PageMetaView;

final readonly class ProductPageView
{
    /**
     * @param list<ProductCardView> $relatedProducts
     * @param list<ProductCertificateView> $certificates
     * @param list<BlogPostView> $relatedPosts
     * @param list<FaqItemView> $faqItems
     */
    public function __construct(
        public PageMetaView $meta,
        public ?ProductPageProductView $product,
        public array $relatedProducts = [],
        public array $certificates = [],
        public array $relatedPosts = [],
        public array $faqItems = [],
    ) {}
}
