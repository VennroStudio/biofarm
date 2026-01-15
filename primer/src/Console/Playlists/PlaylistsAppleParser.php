<?php

declare(strict_types=1);

namespace App\Console\Playlists;

use App\Components\AppleGrab\AppleGrab;
use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use App\Modules\Command\Playlist\AddTrack;
use App\Modules\Command\PossibleArtist\Create;
use App\Modules\Entity\AppleTrack\AppleTrackRepository;
use App\Modules\Entity\ArtistSocial\ArtistSocialRepository;
use App\Modules\Entity\Playlist\Playlist;
use App\Modules\Entity\PlaylistTrack\PlaylistTrackRepository;
use App\Modules\Entity\PlaylistTranslate\PlaylistTranslateRepository;
use App\Modules\Entity\Track\TrackRepository;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use ZayMedia\Shared\Components\Flusher;

use function App\Components\env;

readonly class PlaylistsAppleParser
{
    private string $host;

    public function __construct(
        private AppleGrab $appleGrab,
        private AppleTrackRepository $appleTrackRepository,
        private TrackRepository $trackRepository,
        private PlaylistTrackRepository $playlistTrackRepository,
        private AddTrack\Handler $playlistTrackAddHandler,
        private Create\Handler $possibleArtistCreateHandler,
        private ArtistSocialRepository $artistSocialRepository,
        private PlaylistTranslateRepository $playlistTranslateRepository,
        private Flusher $flusher,
        private RestServiceClient $restServiceClient,
    ) {
        $this->host = env('HOST_API_LO');
    }

    /** @throws Exception|GuzzleException */
    public function handle(Playlist $playlist): void
    {
        $tracks = $this->appleGrab->getAlbumPlaylists($playlist->getIdByUrl());

        $this->playlistTrackRepository->removeAllByPlaylistId($playlist->getId());

        $loPlaylistId = $playlist->getLoPlaylistId();
        $loAudioIds = [];

        /** @var int $number */
        foreach ($tracks as $number => $trackApple) {
            foreach ($trackApple->artists as $artist) {
                $artistSocial = $this->artistSocialRepository->findByTypeAndPartUrl($playlist->getType(), $artist->id);
                if (null !== $artistSocial) {
                    continue;
                }

                $this->possibleArtistCreateHandler->handle(
                    new Create\Command(
                        name: $artist->name,
                        artistId: null,
                        playlistId: $playlist->getId(),
                        spotifyId: null,
                        appleId: $artist->id,
                        tidalId: null
                    )
                );
            }

            $appleTrack = $this->appleTrackRepository->findByAppleId($trackApple->id);
            if (null === $appleTrack) {
                // $log = 'APPLE NOT FOUND: [' . ($number + 1) . '] ' . $trackApple->name . ' - ' . $trackApple->artistsString;
                // echo PHP_EOL . $log;
                continue;
            }

            $track = $this->trackRepository->findByAppleId($appleTrack->getId());
            if (null === $track) {
                // $log = 'NOT FOUND: [' . ($number + 1) . '] ' . $appleTrack->getId() . ' - ' . $appleTrack->getName();
                // echo PHP_EOL . $log;
                continue;
            }

            $this->playlistTrackAddHandler->handle(
                new AddTrack\Command(
                    playlistId: $playlist->getId(),
                    trackId: $track->getId(),
                    number: $number + 1
                )
            );

            $loAudioIds[] = $track->getLoTrackId();
        }

        $playlist->setTotalTracks(\count($tracks));
        $playlist->setCheckedAt(time());

        $this->flusher->flush();

        $countWithoutPhotos = $this->playlistTranslateRepository->getCountWithoutPhoto($playlist->getId());

        if ($countWithoutPhotos === 0 && null !== $loPlaylistId) {
            $this->refreshAudios($playlist->getUserId(), $loPlaylistId, $loAudioIds);
            $this->publish($playlist->getUserId(), $loPlaylistId);
        }
    }

    /** @throws Exception|GuzzleException */
    private function refreshAudios(int $userId, int $playlistId, array $audioIds): void
    {
        $this->restServiceClient->put(
            url: $this->host . '/v1/audio-playlists/' . $playlistId . '/audios',
            body: [
                'audioIds' => $audioIds,
            ],
            accessToken: AccessToken::for((string)$userId)
        );
    }

    /** @throws Exception|GuzzleException */
    private function publish(int $userId, int $playlistId): void
    {
        $this->restServiceClient->post(
            url: $this->host . '/v1/audio-playlists/' . $playlistId . '/publish',
            body: [
                'time' => time(),
            ],
            accessToken: AccessToken::for((string)$userId)
        );
    }
}
