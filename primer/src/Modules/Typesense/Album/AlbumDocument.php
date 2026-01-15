<?php

declare(strict_types=1);

namespace App\Modules\Typesense\Album;

readonly class AlbumDocument
{
    public function __construct(
        public int $id,
        public array $artistIds,
        public string $name,
        public string $nameTranslit,
        public bool $isAlbum,
        public int $totalTracks,
    ) {}
}
