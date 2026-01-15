<?php

declare(strict_types=1);

namespace App\Modules\Command\Category\Create;

final readonly class Command
{
    public function __construct(
        public string $slug,
        public string $name,
    ) {}
}
