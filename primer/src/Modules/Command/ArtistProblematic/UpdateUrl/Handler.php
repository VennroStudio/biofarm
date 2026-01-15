<?php

declare(strict_types=1);

namespace App\Modules\Command\ArtistProblematic\UpdateUrl;

use App\Modules\Entity\ArtistProblematic\ArtistProblematicRepository;
use Throwable;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private ArtistProblematicRepository $artistProblematicRepository,
        private Flusher $flusher,
    ) {}

    /** @throws Throwable */
    public function handle(Command $command): void
    {
        $artistProblematic = $this->artistProblematicRepository->getById($command->artistProblematicId);

        if ($command->tidalUrl !== null) {
            $artistProblematic->setTidalUrl($command->tidalUrl);
        }

        if ($command->spotifyUrl !== null) {
            $artistProblematic->setSpotifyUrl($command->spotifyUrl);
        }

        $this->flusher->flush();
    }
}
