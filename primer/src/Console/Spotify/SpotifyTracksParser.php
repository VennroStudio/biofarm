<?php

declare(strict_types=1);

namespace App\Console\Spotify;

use App\Components\SpotifyGrab\SpotifyGrab;
use App\Modules\Command\Spotify\RefreshTrack;
use App\Modules\Entity\Album\Album;
use Exception;

readonly class SpotifyTracksParser
{
    public function __construct(
        private SpotifyGrab $spotifyGrab,
        private RefreshTrack\Handler $spotifyCreator,
    ) {}

    /** @throws Exception */
    public function handle(Album $album): void
    {
        $spotifyAlbumId = $album->getSpotifyId();

        if (null === $spotifyAlbumId) {
            return;
        }

        $tracks = $this->spotifyGrab->getAlbumTracks($spotifyAlbumId);

        foreach ($tracks as $track) {
            $this->spotifyCreator->handle(
                new RefreshTrack\Command(
                    albumId: $album->getId(),
                    trackId: $track->id,
                    trackLinkedId: $track->linkedId,
                    diskNumber: $track->discNumber,
                    trackNumber: $track->trackNumber,
                    name: $track->name,
                    explicit: $track->explicit,
                    artists: $track->artists,
                    isrc: $track->isrc,
                    duration: $track->duration,
                    type: $track->type,
                    isLocal: $track->isLocal,
                    availableMarkets: $track->availableMarkets
                )
            );
        }
    }
}
