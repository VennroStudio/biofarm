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
        public string $date,
        public string $authorName,
        public int $readTime,
    ) {}
}
