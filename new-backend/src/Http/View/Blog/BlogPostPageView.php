<?php

declare(strict_types=1);

namespace App\Http\View\Blog;

use App\Http\View\PageMetaView;

final readonly class BlogPostPageView
{
    public function __construct(
        public PageMetaView $meta,
        public ?BlogPostView $post,
    ) {}
}
