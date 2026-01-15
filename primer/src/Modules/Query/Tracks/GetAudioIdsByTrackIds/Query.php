<?php

declare(strict_types=1);

namespace App\Modules\Query\Tracks\GetAudioIdsByTrackIds;

final readonly class Query
{
    public function __construct(
        /** @var int[] */
        public array $trackIds,
    ) {}
}
