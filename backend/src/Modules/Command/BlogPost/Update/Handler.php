<?php

declare(strict_types=1);

namespace App\Modules\Command\BlogPost\Update;

use App\Modules\Entity\BlogPost\BlogPost;
use App\Modules\Entity\BlogPost\BlogPostRepository;

final readonly class Handler
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
    ) {}

    public function handle(Command $command): BlogPost
    {
        $post = $this->blogPostRepository->getById($command->postId);

        // Generate slug from title if not provided
        $slug = $command->slug ?? strtolower(trim(preg_replace('/[^\w\s-]/', '', $command->title)));
        $slug = preg_replace('/[-\s]+/', '-', $slug);
        $slug = trim($slug, '-');

        $post->edit(
            title: $command->title,
            excerpt: $command->excerpt,
            content: $command->content,
            image: $command->image,
            categoryId: $command->categoryId,
            readTime: $command->readTime,
            isPublished: $command->isPublished,
            slug: $slug,
        );

        return $post;
    }
}
