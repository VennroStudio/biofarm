<?php

declare(strict_types=1);

namespace App\Modules\Query\TrackProblematic;

final readonly class Query
{
    public function __construct(
        public ?string $search = null,
        public ?int $status = null,
        public string $field = 'id',
        public ?int $artist = null,
        public int $sort = 1,
        public int $count = 50,
        public int $offset = 0,
    ) {}
}
