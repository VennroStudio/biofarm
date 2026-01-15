<?php

declare(strict_types=1);

namespace App\Console\Apple;

use App\Components\AppleGrab\AppleGrab;
use App\Modules\Command\Apple\RefreshTrack;
use App\Modules\Entity\AppleAlbum\AppleAlbum;
use Exception;

readonly class AppleTracksParser
{
    public function __construct(
        private AppleGrab $appleGrab,
        private RefreshTrack\Handler $appleCreator,
    ) {}

    /** @throws Exception */
    public function handle(AppleAlbum $album): void
    {
        $tracks = $this->appleGrab->getAlbumTracks($album->getAppleId());

        foreach ($tracks as $track) {
            $this->appleCreator->handle(
                new RefreshTrack\Command(
                    albumId: $album->getId(),
                    trackId: $track->id,
                    diskNumber: $track->discNumber,
                    trackNumber: $track->trackNumber,
                    isrc: $track->isrc,
                    name: $track->name,
                    artists: $track->artistsString,
                    composers: $track->composers,
                    duration: $track->duration,
                    genreNames: $track->genreNames,
                    attributes: $track->attributes,
                )
            );
        }
    }
}
