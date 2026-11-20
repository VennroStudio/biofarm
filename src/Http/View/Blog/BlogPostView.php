<?php

declare(strict_types=1);

namespace App\Http\View\Blog;

final readonly class BlogPostView
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public ?string $h1,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public string $excerpt,
        public string $content,
        public string $image,
        public ?string $imageAlt,
        public string $category,
        public ?string $categorySlug,
        public string $date,
        public ?string $publishedAt,
        public string $authorName,
        public string $authorAvatar,
        public int $readTime,
    ) {}
}
