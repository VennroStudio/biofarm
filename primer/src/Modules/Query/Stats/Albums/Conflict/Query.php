<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Albums\Conflict;

final readonly class Query
{
    public function __construct(
        public ?int $artistId = null,
        public ?int $status = null,
    ) {}
}
