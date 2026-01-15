<?php

declare(strict_types=1);

namespace App\Console\Loader;

use App\Components\RestServiceClient;
use Exception;
use Throwable;

use function App\Components\env;

readonly class AlbumCoverLoader
{
    private string $host;

    public function __construct(
        private RestServiceClient $restServiceClient,
    ) {
        $this->host = env('HOST_API_LO');
    }

    /** @throws Throwable */
    public function handle(
        int $unionId,
        int $loAlbumId,
        string $accessToken,
        ?string $imagesJson
    ): void {
        if (null === $imagesJson) {
            return;
        }

        /** @var array{url: string, width: int, height: int}[] $images */
        $images = json_decode($imagesJson, true);
        $photo = $this->getMaxImage($images);

        if (\is_string($photo)) {
            $path = $this->downloadPhotoFile($photo);

            if (null === $path) {
                throw new Exception('Empty album photo path');
            }

            if (!$this->uploadPhotoFile($unionId, $loAlbumId, $path, $accessToken)) {
                throw new Exception('Empty photo ID');
            }
        }
    }

    /**
     * @param array{
     *     url: string,
     *     width: int,
     *     height: int
     * }[] $images
     */
    private function getMaxImage(array $images): ?string
    {
        if (!isset($images[0])) {
            return null;
        }

        $maxImage = $images[0];
        $maxRes = $images[0]['width'] * $images[0]['height'];

        foreach ($images as $image) {
            $res = $image['width'] * $image['height'];
            if ($res > $maxRes) {
                $maxRes = $res;
                $maxImage = $image;
            }
        }

        return $maxImage['url'];
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

    private function downloadPhotoFile(string $src): ?string
    {
        try {
            $fileName = $this->getDirTempFiles() . '/' . time() . '_' . rand(10000, 90000) . '.jpg';

            $ch = curl_init($src);

            $fp = fopen($fileName, 'wb');

            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);

            curl_exec($ch);
            curl_close($ch);

            $imageSize = getimagesize($fileName);

            if ($imageSize === false) {
                return null;
            }

            $width = $imageSize[0] ?? 0;
            $height = $imageSize[1] ?? 0;

            if ($width < 200 || $height < 200) {
                return null;
            }

            return $fileName;
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }

        return null;
    }

    /** @throws Throwable */
    private function uploadPhotoFile(int $unionId, int $albumId, string $path, string $accessToken): bool
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

        unlink($path);

        if (!isset($upload['response']['host'], $upload['response']['file_id'])) {
            return false;
        }

        $host = (string)$upload['response']['host'];
        $file_id = (string)$upload['response']['file_id'];

        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; ++$attempt) {
            try {
                $response = $this->restServiceClient->post(
                    url: $this->host . '/v1/audio-albums/' . $albumId . '/photo',
                    body: [
                        'host'   => $host,
                        'fileId' => $file_id,
                    ],
                    accessToken: $accessToken
                );

                if (isset($response['data']['success']) && $response['data']['success'] === 1) {
                    return true;
                }
            } catch (Throwable $exception) {
                if ($attempt === $maxAttempts) {
                    echo PHP_EOL . PHP_EOL . 'Failed to upload PHOTO file (' . $attempt . ')' . PHP_EOL;
                    echo $host . ' ' . $file_id . PHP_EOL;
                    echo $exception->getMessage();
                }
            }
        }
        return false;
    }

    private function getDirTempFiles(): string
    {
        return __DIR__ . '/../../../var/temp';
    }
}
