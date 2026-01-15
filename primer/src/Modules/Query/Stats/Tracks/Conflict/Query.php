<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Tracks\Conflict;

final readonly class Query
{
    public function __construct(
        public ?int $albumId = null,
        public ?int $status = null,
    ) {}
}
