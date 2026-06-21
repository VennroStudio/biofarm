<?php

declare(strict_types=1);

namespace App\Modules\Product\Query\Product\GetById;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProductGetByIdQuery
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $id,
    ) {}
}
