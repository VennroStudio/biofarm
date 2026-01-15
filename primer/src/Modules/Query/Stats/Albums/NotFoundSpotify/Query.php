<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Albums\NotFoundSpotify;

final readonly class Query
{
    public function __construct(
        public ?int $artistId = null,
    ) {}
}
