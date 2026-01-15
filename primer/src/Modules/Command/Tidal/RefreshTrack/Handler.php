<?php

declare(strict_types=1);

namespace App\Modules\Command\Tidal\RefreshTrack;

use App\Modules\Entity\TidalTrack\TidalTrack;
use App\Modules\Entity\TidalTrack\TidalTrackRepository;
use Exception;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private TidalTrackRepository $tidalTrackRepository,
        private Flusher $flusher,
    ) {}

    /** @throws Exception */
    public function handle(Command $command): TidalTrack
    {
        $track = $this->tidalTrackRepository->findByTidalId($command->trackId);

        if (null === $track) {
            if (null === $command->albumId) {
                throw new Exception('Missing album id');
            }

            $track = TidalTrack::create(
                tidalAlbumId: $command->albumId,
                tidalId: $command->trackId,
                diskNumber: $command->diskNumber,
                trackNumber: $command->trackNumber,
                isrc: $command->isrc,
                name: $command->name,
                artists: $command->artists,
                explicit: $command->explicit,
                duration: $command->duration,
                version: $command->version,
                copyright: $command->copyright,
                mediaMetadata: $command->mediaMetadata,
                properties: $command->properties,
                popularity: $command->popularity
            );
        }

        $track->edit(
            diskNumber: $command->diskNumber,
            trackNumber: $command->trackNumber,
            isrc: $command->isrc,
            name: $command->name,
            artists: $command->artists,
            explicit: $command->explicit,
            duration: $command->duration,
            version: $command->version,
            copyright: $command->copyright,
            mediaMetadata: $command->mediaMetadata,
            properties: $command->properties,
            popularity: $command->popularity,
            attributes: $command->attributes
        );

        $this->tidalTrackRepository->add($track);
        $this->flusher->flush();

        return $track;
    }
}
