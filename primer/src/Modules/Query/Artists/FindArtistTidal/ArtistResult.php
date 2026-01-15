<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\FindArtistTidal;

final readonly class ArtistResult
{
    public function __construct(
        public int $id,
        public string $tidal,
        public int $unionId,
    ) {}
}
