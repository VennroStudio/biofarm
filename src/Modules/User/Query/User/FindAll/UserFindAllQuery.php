<?php

declare(strict_types=1);

namespace App\Modules\User\Query\User\FindAll;

use Symfony\Component\Validator\Constraints as Assert;

final class UserFindAllQuery
{
    #[Assert\Positive]
    public int $page = 1;

    #[Assert\Positive]
    #[Assert\LessThanOrEqual(100)]
    public int $perPage = 20;

    public ?string $search = null;

    #[Assert\Date]
    public ?string $dateFrom = null;

    #[Assert\Date]
    public ?string $dateTo = null;

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
