<?php

declare(strict_types=1);

namespace App\Console\Loader;

use App\Components\Exceptions\EmptyTidalTrackIDException;
use App\Components\Exceptions\EmptyTidalTrackPathException;
use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use App\Components\TidalGrab\TidalDL;
use App\Console\HelperData;
use App\Modules\Entity\Album\AlbumRepository;
use App\Modules\Entity\AppleTrack\AppleTrack;
use App\Modules\Entity\AppleTrack\AppleTrackRepository;
use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\TidalAlbum\TidalAlbumRepository;
use App\Modules\Entity\TidalTrack\TidalTrack;
use App\Modules\Entity\TidalTrack\TidalTrackRepository;
use App\Modules\Entity\Track\Track;
use Doctrine\ORM\EntityManagerInterface;
use DuckBug\Duck;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function App\Components\env;

readonly class TrackLoader
{
    private string $host;

    public function __construct(
        private EntityManagerInterface $em,
        private TidalTrackRepository $tidalTrackRepository,
        private AppleTrackRepository $appleTrackRepository,
        private TidalAlbumRepository $tidalAlbumRepository,
        private AlbumRepository $albumRepository,
        private RestServiceClient $restServiceClient,
        private TidalDL $tidalGrab,
        private HelperData $helperData,
        private Duck $duck,
    ) {
        $this->host = env('HOST_API_LO');
    }

    /**
     * @param string[] $spotifySocialIds
     * @throws Throwable
     */
    public function handle(Artist $artist, array $spotifySocialIds, Track $track, int $loAlbumId, OutputInterface $output): void
    {
        if (null !== $track->getLoTrackId()) {
            return;
        }

        try {
            $loAudioId = $this->load(
                unionId: $artist->getUnionId(),
                userId: $artist->getUserId(),
                spotifySocialIds: $spotifySocialIds,
                loAlbumId: $loAlbumId,
                track: $track,
                output: $output
            );
        } catch (EmptyTidalTrackIDException|EmptyTidalTrackPathException $e) {
            if (null !== $track->getTidalTrackId()) {
                $tidalTrack = $this->tidalTrackRepository->getById($track->getTidalTrackId());
                $tidalTrack->setDeleted();

                $album = $this->albumRepository->getById($track->getAlbumId());
                $album->setAllTracksMapped(false);

                $this->em->flush();
            }

            echo PHP_EOL . 'album: ' . $track->getAlbumId() . ', track: ' . $track->getId() . ' - NOT FOUND (' . $e->getMessage() . ')' . PHP_EOL . PHP_EOL;

            $this->duck->warning('TrackLoader -> NOT FOUND', [
                'albumId' => $track->getAlbumId(),
                'trackId' => $track->getId(),
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $lyrics = $this->getLyrics($track);

        $track->setLoTrackId($loAudioId);
        $track->setUploadedAt(time());
        $track->setLyrics($lyrics);
        $this->em->flush();

        $this->updateLyrics($artist->getUserId(), $loAudioId, $lyrics);
    }

    /**
     * @param string[] $spotifySocialIds
     * @throws Throwable
     */
    private function load(int $unionId, int $userId, array $spotifySocialIds, int $loAlbumId, Track $track, OutputInterface $output): int
    {
        $tidalTrackId = $track->getTidalTrackId();
        if (null === $tidalTrackId) {
            throw new EmptyTidalTrackIDException('Empty tidal track ID');
        }

        $tidalTrack = $this->tidalTrackRepository->getById($tidalTrackId);
        $tidalAlbum = $this->tidalAlbumRepository->getById($tidalTrack->getTidalAlbumId());

        $path = $this->downloadSourceFile($tidalAlbum->getTidalId(), $tidalTrack->getTidalId(), $track->getSpotifyDiskNumber());
        if (null === $path) {
            throw new EmptyTidalTrackPathException('Empty track path. TidalAlbumId: ' . $tidalAlbum->getId() . ', TidalTrackId: ' . $tidalTrack->getTidalId());
        }

        $appleTrack = null !== $track->getAppleTrackId() ? $this->appleTrackRepository->findById($track->getAppleTrackId()) : null;

        return $this->uploadSourceFile(
            userId: $userId,
            unionId: $unionId,
            albumId: $loAlbumId,
            spotifySocialIds: $spotifySocialIds,
            path: $path,
            track: $track,
            tidalTrack: $tidalTrack,
            appleTrack: $appleTrack,
            output: $output
        );
    }

    /** @throws Throwable */
    private function getAudioSourceServer(string $accessToken): ?string
    {
        try {
            $response = $this->restServiceClient->get(
                url: $this->host . '/v1/audios/source-server',
                accessToken: $accessToken
            );

            if (isset($response['data']['url'])) {
                return (string)$response['data']['url'];
            }
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }

        return null;
    }

    private function downloadSourceFile(string $_albumId, int $trackId, int $diskNumber): ?string
    {
        if (!$this->tidalGrab->download($trackId)) {
            return null;
        }

        foreach (['flac', 'm4a'] as $ext) {
            $path = $this->getDirTidalFiles() . '/' . $trackId . '/' . $trackId . '.' . $ext;
            if (file_exists($path)) {
                return $path;
            }

            $path = $this->getDirTidalFiles() . '/' . $trackId . '/CD' . $diskNumber . '/' . $trackId . '.' . $ext;
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param string[] $spotifySocialIds
     * @throws Throwable
     */
    private function uploadSourceFile(
        int $userId,
        int $unionId,
        int $albumId,
        array $spotifySocialIds,
        string $path,
        Track $track,
        TidalTrack $tidalTrack,
        ?AppleTrack $appleTrack,
        OutputInterface $output
    ): int {
        $accessToken = $this->getAccessToken($userId);

        $sourceServer = $this->getAudioSourceServer($accessToken);

        if (null === $sourceServer) {
            throw new Exception('Empty sourceServer');
        }

        $upload = $this->restServiceClient->sendFile(
            $sourceServer,
            $path,
            ['union_id' => $unionId, 'album_id' => $albumId]
        );

        unlink($path);

        if (!isset($upload['response']['host'], $upload['response']['file_id'])) {
            throw new Exception('Fail load to file storage');
        }

        $unionNumber = $track->getArtistNumber($spotifySocialIds);

        $host = (string)$upload['response']['host'];
        $file_id = (string)$upload['response']['file_id'];

        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; ++$attempt) {
            try {
                $response = $this->restServiceClient->post(
                    url: $this->host . '/v1/audio-albums/' . $albumId . '/upload',
                    body: [
                        'userId'        => $userId,
                        'albumId'       => $albumId,
                        'host'          => $host,
                        'fileId'        => $file_id,
                        'unionId'       => (null !== $unionNumber) ? $unionId : null,
                        'unionNumber'   => (null !== $unionNumber) ? $unionNumber : null,
                        ...$this->helperData->audio($track, $tidalTrack, $appleTrack, false),
                    ],
                    accessToken: $accessToken
                );

                if (isset($response['data']['id'])) {
                    return (int)$response['data']['id'];
                }
            } catch (Throwable $exception) {
                if ($attempt === $maxAttempts) {
                    $output->writeln('Failed to upload AUDIO file (' . $attempt . ')');
                    $output->writeln($host . ' ' . $file_id);
                    $output->writeln($exception->getMessage());
                }

                $accessToken = $this->getAccessToken($userId);
            }
        }

        throw new Exception('Fail save source');
    }

    /** @throws Throwable */
    private function getLyrics(Track $track): ?string
    {
        $tidalTrackId = $track->getTidalTrackId();
        if (null === $tidalTrackId) {
            return null;
        }

        $tidalTrack = $this->tidalTrackRepository->getById($tidalTrackId);
        // $tidalAlbum = $this->tidalAlbumRepository->getById($tidalTrack->getTidalAlbumId());

        $path = $this->getDirTidalFiles() . '/' . $tidalTrack->getTidalId() . '/' . $tidalTrack->getTidalId() . '.lrc';

        if (!file_exists($path)) {
            $path = $this->getDirTidalFiles() . '/' . $tidalTrack->getTidalId() . '/CD' . $track->getSpotifyDiskNumber() . '/' . $tidalTrack->getTidalId() . '.lrc';
        }

        if (!file_exists($path)) {
            return null;
        }

        $lyrics = file_get_contents($path);
        if (!\is_string($lyrics) || empty($lyrics)) {
            return null;
        }

        return $lyrics;
    }

    /** @throws Throwable */
    private function updateLyrics(int $userId, int $audioId, ?string $lyrics): void
    {
        if (null === $lyrics) {
            return;
        }

        $accessToken = $this->getAccessToken($userId);

        $this->restServiceClient->post(
            url: $this->host . '/v1/audios/' . $audioId . '/lyrics',
            body: [
                'lyrics' => $lyrics,
            ],
            accessToken: $accessToken
        );
    }

    private function getDirTidalFiles(): string
    {
        return __DIR__ . '/../../../var/tidal/tracks';
    }

    /** @throws Exception */
    private function getAccessToken(int $userId): string
    {
        return AccessToken::for((string)$userId);
    }
}
