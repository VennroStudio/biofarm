<?php

declare(strict_types=1);

namespace App\Http\View;

final readonly class MetricView
{
    public function __construct(
        public string $label,
        public string $value,
        public string $description,
    ) {}
}
