<?php

declare(strict_types=1);

namespace App\Http\View\Blog;

use App\Http\View\PageMetaView;

final readonly class BlogPageView
{
    /**
     * @param list<BlogPostView> $posts
     */
    public function __construct(
        public PageMetaView $meta,
        public array $posts,
    ) {}
}
