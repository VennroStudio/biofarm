<?php

declare(strict_types=1);

namespace App\Components\Storage;

final readonly class UploadedFileResult
{
    public function __construct(
        public string $path,
        public string $url,
        public string $mimeType,
        public int $size,
        public ?int $width,
        public ?int $height,
    ) {}
}
