<?php

declare(strict_types=1);

namespace App\Modules\Command\Apple\RefreshTrack;

use App\Modules\Entity\AppleTrack\AppleTrack;
use App\Modules\Entity\AppleTrack\AppleTrackRepository;
use Exception;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private AppleTrackRepository $appleTrackRepository,
        private Flusher $flusher,
    ) {}

    /** @throws Exception */
    public function handle(Command $command): AppleTrack
    {
        $track = $this->appleTrackRepository->findByAppleId($command->trackId);

        if (null === $track) {
            if (null === $command->albumId) {
                throw new Exception('Missing album id');
            }

            $track = AppleTrack::create(
                appleAlbumId: $command->albumId,
                appleId: $command->trackId,
                diskNumber: $command->diskNumber,
                trackNumber: $command->trackNumber,
                isrc: $command->isrc,
                name: $command->name,
                artists: $command->artists,
                composers: $command->composers,
                duration: $command->duration,
                genreNames: $command->genreNames,
                attributes: $command->attributes,
            );
        }

        $track->edit(
            diskNumber: $command->diskNumber,
            trackNumber: $command->trackNumber,
            isrc: $command->isrc,
            name: $command->name,
            artists: $command->artists,
            composers: $command->composers,
            duration: $command->duration,
            genreNames: $command->genreNames,
            attributes: $command->attributes
        );

        $this->appleTrackRepository->add($track);
        $this->flusher->flush();

        return $track;
    }
}
