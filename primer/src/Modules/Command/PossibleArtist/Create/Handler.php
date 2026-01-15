<?php

declare(strict_types=1);

namespace App\Modules\Command\PossibleArtist\Create;

use App\Modules\Entity\PossibleArtist\PossibleArtist;
use App\Modules\Entity\PossibleArtist\PossibleArtistRepository;
use Exception;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private PossibleArtistRepository $possibleArtistRepository,
        private Flusher $flusher,
    ) {}

    /** @throws Exception */
    public function handle(Command $command): PossibleArtist
    {
        $possibleArtist = null;

        if (null !== $command->spotifyId) {
            $possibleArtist = $this->possibleArtistRepository->findBySpotifyId($command->spotifyId);
        }

        if (null !== $command->appleId) {
            $possibleArtist = $this->possibleArtistRepository->findByAppleId($command->appleId);
        }

        if (null !== $command->tidalId) {
            $possibleArtist = $this->possibleArtistRepository->findByTidalId($command->tidalId);
        }

        if (null !== $possibleArtist) {
            return $possibleArtist;
        }

        $possibleArtist = PossibleArtist::create(
            name: $command->name,
            playlistId: $command->playlistId,
            spotifyId: $command->spotifyId,
            appleId: $command->appleId,
            tidalId: $command->tidalId
        );

        $this->possibleArtistRepository->add($possibleArtist);
        $this->flusher->flush();

        return $possibleArtist;
    }
}
