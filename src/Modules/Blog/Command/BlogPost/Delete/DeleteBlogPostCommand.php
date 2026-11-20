<?php

declare(strict_types=1);

namespace App\Modules\Blog\Command\BlogPost\Delete;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeleteBlogPostCommand
{
    public function __construct(
        #[Assert\NotBlank]
        public int $postId,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
    ) {}
}
