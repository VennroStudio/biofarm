<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Playlists;

final readonly class Query
{
    public function __construct(
        public ?string $search = null,
        public ?int $type = null,
        public ?int $priority = null,
        public ?int $isFollowed = null,
        public ?int $source = null,
        public ?string $field = null,
        public int $sort = 1,
        public int $offset = 0,
    ) {}
}
