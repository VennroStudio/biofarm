<?php

declare(strict_types=1);

namespace App\Modules\Command\TrackProblematic\UpdateUrl;

use App\Modules\Entity\TrackProblematic\TrackProblematicRepository;
use Throwable;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private TrackProblematicRepository $trackProblematicRepository,
        private Flusher $flusher,
    ) {}

    /** @throws Throwable */
    public function handle(Command $command): void
    {
        $trackProblematic = $this->trackProblematicRepository->getById($command->trackProblematicId);

        if ($command->tidalUrl !== null) {
            $trackProblematic->setTidalUrl($command->tidalUrl);
        }

        if ($command->spotifyUrl !== null) {
            $trackProblematic->setSpotifyUrl($command->spotifyUrl);
        }

        $this->flusher->flush();
    }
}
