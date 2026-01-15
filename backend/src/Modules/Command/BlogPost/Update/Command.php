<?php

declare(strict_types=1);

namespace App\Modules\Command\BlogPost\Update;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $postId,
        #[Assert\NotBlank]
        public string $title,
        #[Assert\NotBlank]
        public string $excerpt,
        #[Assert\NotBlank]
        public string $content,
        #[Assert\NotBlank]
        public string $image,
        #[Assert\NotBlank]
        public string $categoryId,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $readTime,
        public bool $isPublished = false,
        public ?string $slug = null,
    ) {}
}
