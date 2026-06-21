<?php

declare(strict_types=1);

namespace App\Modules\Product\Query\ProductCategory\FindAll;

use Symfony\Component\Validator\Constraints as Assert;

final class ProductCategoryFindAllQuery
{
    #[Assert\Positive]
    public int $page = 1;

    #[Assert\Positive]
    #[Assert\LessThanOrEqual(100)]
    public int $perPage = 100;

    public bool $activeOnly = false;

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
