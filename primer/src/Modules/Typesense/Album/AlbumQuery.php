<?php

declare(strict_types=1);

namespace App\Modules\Typesense\Album;

class AlbumQuery
{
    public function __construct(
        public string $search,
        public int $artistId,
        public ?bool $isAlbum = null,
        public int $limit = 150
    ) {}
}
