<?php

declare(strict_types=1);

namespace App\Modules\Blog\Query\BlogPost\GetById;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BlogPostGetByIdQuery
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $id,
        public bool $onlyPublished = true,
    ) {}
}
