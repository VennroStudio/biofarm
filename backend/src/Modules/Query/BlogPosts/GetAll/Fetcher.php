<?php

declare(strict_types=1);

namespace App\Modules\Query\BlogPosts\GetAll;

use App\Modules\Entity\BlogPost\BlogPost;
use App\Modules\Entity\BlogPost\BlogPostRepository;

final readonly class Fetcher
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
    ) {}

    /** @return BlogPost[] */
    public function fetch(Query $query): array
    {
        if ($query->onlyPublished) {
            return $this->blogPostRepository->findAllPublished();
        }
        return $this->blogPostRepository->findAll();
    }
}
