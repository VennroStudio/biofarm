<?php

declare(strict_types=1);

namespace App\Components\App;

final readonly class AppInfo
{
    public function __construct(
        public string $name,
        public string $environment,
        public string $version,
        public string $timezone,
    ) {}
}
