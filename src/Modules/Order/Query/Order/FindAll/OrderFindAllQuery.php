<?php

declare(strict_types=1);

namespace App\Modules\Order\Query\Order\FindAll;

use Symfony\Component\Validator\Constraints as Assert;

final class OrderFindAllQuery
{
    #[Assert\Positive]
    public int $page = 1;

    #[Assert\Range(min: 1, max: 100)]
    public int $perPage = 100;

    #[Assert\Positive]
    public ?int $userId = null;

    #[Assert\Length(max: 100)]
    public ?string $referredBy = null;

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
