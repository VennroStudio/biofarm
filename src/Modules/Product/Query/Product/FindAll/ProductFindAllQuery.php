<?php

declare(strict_types=1);

namespace App\Modules\Product\Query\Product\FindAll;

use Symfony\Component\Validator\Constraints as Assert;

final class ProductFindAllQuery
{
    #[Assert\Positive]
    public int $page = 1;

    #[Assert\Positive]
    #[Assert\LessThanOrEqual(100)]
    public int $perPage = 100;

    public ?string $category = null;

    public bool $includeInactive = false;

    public ?string $search = null;

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
