<?php

declare(strict_types=1);

namespace App\Modules\Query\Products\GetBySlug;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Query
{
    public function __construct(
        #[Assert\NotBlank]
        public string $slug,
    ) {}
}
