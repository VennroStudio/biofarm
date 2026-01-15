<?php

declare(strict_types=1);

namespace App\Modules\Query\GetNextArtistTidal;

final readonly class Query
{
    public function __construct(
        public int $mode = 0,
    ) {}
}
