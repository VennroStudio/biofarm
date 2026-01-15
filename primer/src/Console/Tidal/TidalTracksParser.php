<?php

declare(strict_types=1);

namespace App\Console\Tidal;

use App\Components\TidalGrab\TidalGrab;
use App\Modules\Command\Tidal\RefreshTrack;
use App\Modules\Entity\TidalAlbum\TidalAlbum;
use Exception;

readonly class TidalTracksParser
{
    public function __construct(
        private TidalGrab $tidalGrab,
        private RefreshTrack\Handler $tidalCreator,
    ) {}

    /** @throws Exception */
    public function handle(TidalAlbum $album): void
    {
        $tracks = $this->tidalGrab->getAlbumTracks($album->getTidalId());

        foreach ($tracks as $track) {
            $this->tidalCreator->handle(
                new RefreshTrack\Command(
                    albumId: $album->getId(),
                    trackId: $track->id,
                    diskNumber: $track->discNumber,
                    trackNumber: $track->trackNumber,
                    isrc: $track->isrc,
                    name: $track->name,
                    artists: $track->artists,
                    explicit: $track->explicit,
                    duration: $track->duration,
                    version: $track->version,
                    copyright: $track->copyright,
                    mediaMetadata: $track->mediaMetadata,
                    properties: $track->properties,
                    popularity: $track->popularity,
                    attributes: $track->artists,
                )
            );
        }
    }
}
