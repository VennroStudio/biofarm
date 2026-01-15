<?php

declare(strict_types=1);

namespace App\Modules\Query\Tracks\GetTrackIdsByTidalIds;

final readonly class Query
{
    public function __construct(
        /** @var string[] */
        public array $tidalIds,
    ) {}
}
