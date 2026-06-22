<?php

declare(strict_types=1);

namespace App\Http\View;

final readonly class PageMetaView
{
    public function __construct(
        public string $title,
        public string $description,
    ) {}
}
