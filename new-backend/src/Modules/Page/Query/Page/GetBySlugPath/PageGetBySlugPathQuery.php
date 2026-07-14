<?php

declare(strict_types=1);

namespace App\Modules\Page\Query\Page\GetBySlugPath;

final readonly class PageGetBySlugPathQuery
{
    public function __construct(
        public string $slugPath,
    ) {}
}
