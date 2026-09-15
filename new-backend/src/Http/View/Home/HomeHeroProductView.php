<?php

declare(strict_types=1);

namespace App\Http\View\Home;

final readonly class HomeHeroProductView
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $image,
    ) {}
}
