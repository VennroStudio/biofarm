<?php

declare(strict_types=1);

namespace App\Components\YoutubeGrab;

use YouTube\Models\StreamFormat;

readonly class MusicResult
{
    public function __construct(
        public string $id,
        public string $name,
        public array $artists,
        public string $duration,
        public int $durationSeconds,
        public ?StreamFormat $stream,
    ) {}
}
