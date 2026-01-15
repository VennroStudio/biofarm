<?php

declare(strict_types=1);

namespace App\Modules\Command\ArtistProblematic\Add;

final readonly class Command
{
    public function __construct(
        public int $artistId,
        public string $artistName,
    ) {}
}
