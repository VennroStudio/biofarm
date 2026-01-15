<?php

declare(strict_types=1);

namespace App\Modules\Typesense\Track;

readonly class TrackDocument
{
    public function __construct(
        public int $id,
        public int $albumId,
        public ?string $isrc,
        public string $name,
        public string $nameTranslit,
        public int $diskNumber,
    ) {}
}
