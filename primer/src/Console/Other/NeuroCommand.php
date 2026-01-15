<?php

declare(strict_types=1);

namespace App\Console\Other;

use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use App\Console\HelperData;
use App\Modules\Entity\AppleTrack\AppleTrackRepository;
use App\Modules\Entity\TidalTrack\TidalTrackRepository;
use App\Modules\Entity\Track\TrackRepository;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function App\Components\env;

class NeuroCommand extends Command
{
    private string $host;

    public function __construct(
        private readonly TrackRepository $trackRepository,
        private readonly TidalTrackRepository $tidalTrackRepository,
        private readonly AppleTrackRepository $appleTrackRepository,
        private readonly HelperData $helperData,
        private readonly RestServiceClient $restServiceClient,
    ) {
        parent::__construct();

        $this->host = env('HOST_API_LO');
    }

    protected function configure(): void
    {
        $this
            ->setName('other:neuro')
            ->setDescription('Load neuro albums command');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = __DIR__ . '/../../../var/temp/neuro/';

        $userId = 4676;
        $unionId = 4792;

        // -------------------------------------

        //  $albumId = 712575;
        //  $fileName = '3332082-neuro.wav';
        //  $isNeuro = true;
        //
        //  $data = [
        //      'name'          => 'Какао',
        //      'isrc'          => 'RUAGT2340987',
        //      'isrcOther'     => [],
        //      'diskNumber'    => 1,
        //      'trackNumber'   => 1,
        //      'artists'       => 'RAIM TRIGER',
        //      'duration'      => 172,
        //      'isExplicit'    => false,
        //      'isDolbyAtmos'  => false,
        //      'unionNumbers'  => [],
        //      'genres'        => [],
        //  ];
        //  $this->loadManual($userId, $unionId, $albumId, $dir . 'audios/' . $fileName, $data, $isNeuro);
        //  return 0;
        // -------------------------------------

        $accessToken = AccessToken::for((string)$userId);

        $albumId = $this->createAlbumLO(
            unionId: $unionId,
            unionNumber: 1,
            name: 'Алёна UNI - Нейроверсия',
            upc: 'LO-000000003',
            type: 'album',
            artists: 'Алёна UNI',
            releasedAt: strtotime(date('Y-m-d 03:00:00')),
            accessToken: $accessToken
        );

        $isCoverLoaded = $this->loadCover($unionId, $albumId, $dir . 'cover.jpg', $accessToken);

        if (!$isCoverLoaded) {
            throw new Exception('Cover image not found');
        }

        $this->loadTracks($userId, $unionId, $albumId, $dir . 'audios');

        $output->writeln('<info>Done! AlbumId: ' . $albumId . '</info>');

        return 0;
    }

    /**
     * @param array{
     *      name: string,
     *      isrc: string|null,
     *      isrcOther: string[],
     *      diskNumber: int,
     *      trackNumber: int,
     *      artists: string,
     *      duration: int,
     *      isExplicit: bool,
     *      isDolbyAtmos: bool,
     *      unionNumbers: array{
     *          unionId: int,
     *          number: int
     *      }[],
     *      genres: string[]
     *  } $data
     * @throws Throwable
     */
    private function loadManual(int $userId, int $unionId, int $albumId, string $path, array $data, bool $isNeuro): void
    {
        $accessToken = AccessToken::for((string)$userId);
        $this->uploadSourceFile($userId, $unionId, $albumId, $path, $data['trackNumber'], $accessToken, $data, $isNeuro);
    }

    /** @throws Throwable */
    private function createAlbumLO(
        int $unionId,
        ?int $unionNumber,
        string $name,
        string $upc,
        string $type,
        string $artists,
        int $releasedAt,
        string $accessToken
    ): int {
        $response = $this->restServiceClient->post(
            url: $this->host . '/v1/audio-albums',
            body: [
                'unionId'               => $unionId,
                'unionNumber'           => $unionNumber,
                'name'                  => $name,
                'upc'                   => $upc,
                'upcOther'              => [],
                'type'                  => $type,
                'artists'               => $artists,
                'label'                 => null,
                'isExplicit'            => false,
                'releasedAt'            => $releasedAt,
                'releasedAtPrecision'   => 'day',
                'genres'                => [],
            ],
            accessToken: $accessToken
        );

        if (isset($response['data']['id'])) {
            return (int)$response['data']['id'];
        }

        throw new Exception('Can not create LO album');
    }

    /** @throws Throwable */
    private function loadCover(int $unionId, int $albumId, string $path, string $accessToken): bool
    {
        $photoServer = $this->getAlbumPhotoServer($accessToken);

        if (null === $photoServer) {
            return false;
        }

        try {
            $upload = $this->restServiceClient->sendFile($photoServer, $path, ['union_id' => $unionId, 'album_id' => $albumId]);
        } catch (Throwable $exception) {
            echo $exception->getMessage();
        }

        if (!isset($upload['response']['host'], $upload['response']['file_id'])) {
            return false;
        }

        $response = $this->restServiceClient->post(
            url: $this->host . '/v1/audio-albums/' . $albumId . '/photo',
            body: [
                'host' => $upload['response']['host'],
                'fileId' => $upload['response']['file_id'],
            ],
            accessToken: $accessToken
        );

        if (isset($response['data']['success']) && $response['data']['success'] === 1) {
            return true;
        }

        return false;
    }

    /** @throws Throwable */
    private function getAlbumPhotoServer(string $accessToken): ?string
    {
        try {
            $response = $this->restServiceClient->get(
                url: $this->host . '/v1/audio-albums/photo-server',
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

    /** @throws Throwable */
    private function loadTracks(int $userId, int $unionId, int $albumId, string $dir): void
    {
        /** @var string[] $files */
        $files = array_diff(scandir($dir), ['.', '..']);

        $number = 1;

        foreach ($files as $file) {
            echo $file . PHP_EOL;
            $audioId = explode('.', $file)[0];

            $track = $this->trackRepository->findByLoAudioId($audioId);
            if (null === $track) {
                continue;
            }

            $tidalTrackId = $track->getTidalTrackId();
            if (null === $tidalTrackId) {
                continue;
            }

            $accessToken = AccessToken::for((string)$userId);

            $tidalTrack = $this->tidalTrackRepository->getById($tidalTrackId);
            $appleTrack = null !== $track->getAppleTrackId() ? $this->appleTrackRepository->findById($track->getAppleTrackId()) : null;

            $data = $this->helperData->audio($track, $tidalTrack, $appleTrack, false);

            $path = $this->download($dir, $audioId, $accessToken);
            $this->uploadSourceFile($userId, $unionId, $albumId, $path, $number, $accessToken, $data, false);
            ++$number;

            $this->uploadSourceFile($userId, $unionId, $albumId, $dir . '/' . $file, $number, $accessToken, $data, true);
            ++$number;
        }
    }

    /** @throws Throwable */
    private function download(string $dir, string $audioId, string $accessToken): string
    {
        /** @var array{data: array{source: array{mp3: string|null, flac: string|null}}} $response */
        $response = $this->restServiceClient->get(
            url: $this->host . '/v1/audios/' . $audioId,
            accessToken: $accessToken
        );
        $url = $response['data']['source']['mp3'] ?? $response['data']['source']['flac'] ?? null;

        if (null === $url) {
            throw new Exception('Can not download audio file');
        }

        $ext = (null !== $response['data']['source']['mp3']) ? 'mp3' : 'flac';

        $stream = fopen($url, 'rb');
        $data = stream_get_contents($stream);
        fclose($stream);

        $filename = $dir . '/download/' . $audioId . '.' . $ext;

        file_put_contents($filename, $data);

        return $filename;
    }

    /**
     * @param array{
     *      name: string,
     *      isrc: string|null,
     *      isrcOther: string[],
     *      diskNumber: int,
     *      trackNumber: int,
     *      artists: string,
     *      duration: int,
     *      isExplicit: bool,
     *      isDolbyAtmos: bool,
     *      unionNumbers: array{
     *          unionId: int,
     *          number: int
     *      }[],
     *      genres: string[]
     *  } $data
     * @throws Throwable
     */
    private function uploadSourceFile(
        int $userId,
        int $unionId,
        int $albumId,
        string $path,
        int $number,
        string $accessToken,
        array $data,
        bool $isNeuro
    ): void {
        $sourceServer = $this->getAudioSourceServer($accessToken);

        if (null === $sourceServer) {
            throw new Exception('Empty sourceServer');
        }

        $upload = $this->restServiceClient->sendFile(
            $sourceServer,
            $path,
            ['union_id' => $unionId, 'album_id' => $albumId]
        );

        if (!isset($upload['response']['host'], $upload['response']['file_id'])) {
            throw new Exception('Fail load to file storage');
        }

        $data['diskNumber'] = 1;
        $data['trackNumber'] = $number;

        if ($isNeuro) {
            $data['name'] .= ' (Нейроверсия)';
            $data['isrc'] = 'LO' . ($data['isrc'] ?? time());
            $data['isrcOther'] = [];
        }

        $response = $this->restServiceClient->post(
            url: $this->host . '/v1/audio-albums/' . $albumId . '/upload',
            body: [
                'userId'        => $userId,
                'albumId'       => $albumId,
                'host'          => $upload['response']['host'],
                'fileId'        => $upload['response']['file_id'],
                'unionId'       => $unionId,
                'unionNumber'   => 1,
                ...$data,
            ],
            accessToken: $accessToken
        );

        if (!isset($response['data']['id'])) {
            throw new Exception('Fail save source');
        }
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
}
