<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\GetArtistIdsByTidalIds;

final readonly class Query
{
    public function __construct(
        /** @var string[] */
        public array $tidalIds,
    ) {}
}
