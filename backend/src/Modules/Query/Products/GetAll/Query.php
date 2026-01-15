<?php

declare(strict_types=1);

namespace App\Modules\Query\Products\GetAll;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Query
{
    public function __construct(
        public ?string $categoryId = null,
    ) {}
}
