<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\FindArtistTidal;

final readonly class Query
{
    public function __construct(
        public int $id,
    ) {}
}
