<?php

declare(strict_types=1);

namespace App\Modules\Page\Query\Page\FindAll;

final readonly class PageFindAllQuery
{
    public function __construct(
        public bool $includeUnpublished = true,
    ) {}
}
