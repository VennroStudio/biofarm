<?php

declare(strict_types=1);

namespace App\Modules\Command\BlogPost\Delete;

use App\Modules\Entity\BlogPost\BlogPostRepository;
use Exception;

final readonly class Handler
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
    ) {}

    public function handle(Command $command): void
    {
        $post = $this->blogPostRepository->getById($command->postId);
        $this->blogPostRepository->remove($post);
    }
}
