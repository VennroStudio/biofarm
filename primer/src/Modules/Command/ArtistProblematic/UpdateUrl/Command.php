<?php

declare(strict_types=1);

namespace App\Modules\Command\ArtistProblematic\UpdateUrl;

final readonly class Command
{
    public function __construct(
        public int $artistProblematicId,
        public ?string $tidalUrl = null,
        public ?string $spotifyUrl = null,
    ) {}
}
