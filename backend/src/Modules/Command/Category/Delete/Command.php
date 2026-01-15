<?php

declare(strict_types=1);

namespace App\Modules\Command\Category\Delete;

final readonly class Command
{
    public function __construct(
        public int $categoryId,
    ) {}
}
