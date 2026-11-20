<?php

declare(strict_types=1);

namespace App\Modules\Blog\Query\BlogPost\FindAll;

use Symfony\Component\Validator\Constraints as Assert;

final class BlogPostFindAllQuery
{
    #[Assert\Positive]
    public int $page = 1;

    #[Assert\Range(min: 1, max: 100)]
    public int $perPage = 100;

    public bool $onlyPublished = true;

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
