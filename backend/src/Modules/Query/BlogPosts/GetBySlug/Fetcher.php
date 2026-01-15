<?php

declare(strict_types=1);

namespace App\Modules\Query\BlogPosts\GetBySlug;

use App\Modules\Entity\BlogPost\BlogPost;
use App\Modules\Entity\BlogPost\BlogPostRepository;

final readonly class Fetcher
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
    ) {}

    public function fetch(Query $query): ?BlogPost
    {
        return $this->blogPostRepository->findBySlug($query->slug);
    }
}
