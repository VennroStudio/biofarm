<?php

declare(strict_types=1);

namespace App\Modules\Blog\Command\BlogPost\Update;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateBlogPostCommand
{
    public function __construct(
        #[Assert\NotBlank]
        public int $postId,
        #[Assert\NotBlank(message: 'validation.blog_post_title_required')]
        public string $title,
        #[Assert\NotBlank(message: 'validation.blog_post_excerpt_required')]
        public string $excerpt,
        #[Assert\NotBlank(message: 'validation.blog_post_content_required')]
        public string $content,
        #[Assert\NotBlank(message: 'validation.blog_post_image_required')]
        public string $image,
        #[Assert\NotBlank(message: 'validation.blog_post_category_required')]
        public string $categoryId,
        #[Assert\NotBlank(message: 'validation.blog_post_author_required')]
        public string $authorName,
        #[Assert\Positive(message: 'validation.blog_post_read_time_positive')]
        public int $readTime,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
        public ?string $slug = null,
        public bool $isPublished = false,
    ) {}
}
