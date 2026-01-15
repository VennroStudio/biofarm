<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\PossibleArtists;

final readonly class Query
{
    public function __construct(
        public ?string $search = null,
        public ?int $playlist_id = null,
        public ?int $source = null,
        public ?string $field = null,
        public int $sort = 1,
        public int $offset = 0,
    ) {}
}
