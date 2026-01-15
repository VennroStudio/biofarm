<?php

declare(strict_types=1);

namespace App\Modules\Command\Spotify\RefreshTrack;

use App\Modules\Entity\Track\Track;
use App\Modules\Entity\Track\TrackRepository;
use Exception;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private TrackRepository $trackRepository,
        private Flusher $flusher,
    ) {}

    /** @throws Exception */
    public function handle(Command $command): Track
    {
        if (null !== $command->trackLinkedId) {
            $track = $this->trackRepository->findBySpotifyId($command->trackLinkedId);
        } else {
            $track = $this->trackRepository->findBySpotifyId($command->trackId);
        }

        $originalId = $command->trackLinkedId !== null ? $command->trackId : null;

        if (null === $track) {
            if (null === $command->albumId) {
                throw new Exception('Missing album id');
            }

            $track = Track::createSpotify(
                albumId: $command->albumId,
                spotifyId: $command->trackLinkedId ?? $command->trackId,
                spotifyOriginalId: $originalId,
                diskNumber: $command->diskNumber,
                trackNumber: $command->trackNumber,
                name: $command->name,
                explicit: $command->explicit,
                duration: $command->duration
            );
        }

        $track->editSpotifyInfo(
            spotifyOriginalId: $originalId,
            diskNumber: $command->diskNumber,
            trackNumber: $command->trackNumber,
            name: $command->name,
            explicit: $command->explicit,
            artists: $command->artists,
            isrc: $command->isrc,
            duration: $command->duration,
            type: $command->type,
            isLocal: $command->isLocal,
            availableMarkets: $command->availableMarkets
        );

        $this->trackRepository->add($track);
        $this->flusher->flush();

        return $track;
    }
}
