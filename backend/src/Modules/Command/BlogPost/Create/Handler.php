<?php

declare(strict_types=1);

namespace App\Modules\Command\BlogPost\Create;

use App\Modules\Entity\BlogPost\BlogPost;
use App\Modules\Entity\BlogPost\BlogPostRepository;
use DomainException;

final readonly class Handler
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
    ) {}

    public function handle(Command $command): BlogPost
    {
        $existing = $this->blogPostRepository->findBySlugAll($command->slug);

        if ($existing) {
            throw new DomainException('BlogPost with this slug already exists');
        }

        $post = BlogPost::create(
            slug: $command->slug,
            title: $command->title,
            excerpt: $command->excerpt,
            content: $command->content,
            image: $command->image,
            categoryId: $command->categoryId,
            authorName: $command->authorName,
            readTime: $command->readTime,
            isPublished: $command->isPublished,
        );

        $this->blogPostRepository->add($post);

        return $post;
    }
}
