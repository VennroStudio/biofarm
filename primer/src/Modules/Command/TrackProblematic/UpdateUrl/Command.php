<?php

declare(strict_types=1);

namespace App\Modules\Command\TrackProblematic\UpdateUrl;

final readonly class Command
{
    public function __construct(
        public int $trackProblematicId,
        public ?string $tidalUrl = null,
        public ?string $spotifyUrl = null,
    ) {}
}
