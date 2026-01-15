<?php

declare(strict_types=1);

namespace App\Modules\Command\Category\Update;

final readonly class Command
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $slug = null,
    ) {}
}
