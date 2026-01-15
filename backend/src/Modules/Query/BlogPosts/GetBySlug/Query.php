<?php

declare(strict_types=1);

namespace App\Modules\Query\BlogPosts\GetBySlug;

final readonly class Query
{
    public function __construct(
        public string $slug,
    ) {}
}
