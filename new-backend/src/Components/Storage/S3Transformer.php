<?php

declare(strict_types=1);

namespace App\Components\Storage;

final readonly class S3Transformer
{
    public function __construct(
        private string $s3PublicUrl,
    ) {}

    public function buildUrl(?string $path): ?string
    {
        return $path !== null ? $this->s3PublicUrl . '/' . $path : null;
    }
}
