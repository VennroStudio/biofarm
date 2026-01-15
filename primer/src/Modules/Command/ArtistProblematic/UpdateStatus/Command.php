<?php

declare(strict_types=1);

namespace App\Modules\Command\ArtistProblematic\UpdateStatus;

final readonly class Command
{
    /**
     * @param int|int[] $artistId
     */
    public function __construct(
        public array|int $artistId,
        public int $status,
    ) {}
}
