<?php

declare(strict_types=1);

namespace App\Components\Storage;

final readonly class CompressedImage
{
    public function __construct(
        public string $path,
        public string $mime,
    ) {}
}
