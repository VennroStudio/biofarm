<?php

declare(strict_types=1);

namespace App\Modules\Typesense\Track;

class TrackQuery
{
    public function __construct(
        public string $search,
        public int $albumId,
        public ?string $isrc,
        public ?int $diskNumber,
        public int $limit = 150
    ) {}
}
