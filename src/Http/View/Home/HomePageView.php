<?php

declare(strict_types=1);

namespace App\Http\View\Home;

use App\Http\View\Blog\BlogPostView;
use App\Http\View\Catalog\CatalogFacetView;
use App\Http\View\Certificate\CertificateView;
use App\Http\View\PageMetaView;

final readonly class HomePageView
{
    /**
     * @param list<HomeHeroProductView> $heroProducts
     * @param list<CatalogFacetView> $purposes
     * @param array<string, string> $purposeUrls
     * @param list<BlogPostView> $blogPosts
     * @param list<CertificateView> $certificates
     * @param list<HomeReviewView> $reviews
     */
    public function __construct(
        public PageMetaView $meta,
        public HomeCatalogView $catalog,
        public array $heroProducts,
        public array $purposes,
        public array $purposeUrls,
        public array $blogPosts,
        public array $certificates,
        public array $reviews,
    ) {}
}
