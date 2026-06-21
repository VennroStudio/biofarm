<?php

declare(strict_types=1);

namespace App\Modules\Review\Query\Review\GetById;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ReviewGetByIdQuery
{
    public function __construct(
        #[Assert\NotBlank]
        public string $id,
    ) {}
}
