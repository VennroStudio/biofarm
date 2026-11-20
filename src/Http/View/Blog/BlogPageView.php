<?php

declare(strict_types=1);

namespace App\Http\View\Blog;

use App\Http\View\PageMetaView;

final readonly class BlogPageView
{
    /**
     * @param list<BlogPostView> $posts
     * @param list<BlogPostView> $otherPosts
     * @param list<string> $categories
     * @param array<string, string> $categoryUrls
     * @param list<array{type: string, page?: int, url?: string, current?: bool}> $paginationItems
     */
    public function __construct(
        public PageMetaView $meta,
        public array $posts,
        public ?BlogPostView $featuredPost,
        public array $otherPosts,
        public array $categories,
        public array $categoryUrls,
        public string $selectedCategory,
        public string $searchQuery,
        public int $currentPage,
        public int $totalPages,
        public int $totalPosts,
        public array $paginationItems,
        public ?string $previousPageUrl,
        public ?string $nextPageUrl,
    ) {}
}
