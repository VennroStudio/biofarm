<?php

declare(strict_types=1);

namespace App\Http\View\Blog;

final readonly class BlogPostView
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public string $excerpt,
        public string $content,
        public string $image,
        public string $category,
        public string $date,
        public string $authorName,
        public string $authorAvatar,
        public int $readTime,
    ) {}
}
