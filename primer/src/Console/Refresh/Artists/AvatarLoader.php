<?php

declare(strict_types=1);

namespace App\Console\Refresh\Artists;

use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use Exception;
use Throwable;

use function App\Components\env;

readonly class AvatarLoader
{
    private string $host;

    public function __construct(
        private RestServiceClient $restServiceClient,
    ) {
        $this->host = env('HOST_API_LO');
    }

    /** @throws Throwable */
    public function handle(int $userId, int $unionId, string $url): void
    {
        $accessToken = AccessToken::for((string)$userId);

        $path = $this->downloadPhotoFile($url);

        if (null === $path) {
            throw new Exception('Empty url path');
        }

        if (!$this->uploadPhotoFile($unionId, $path, $accessToken)) {
            throw new Exception('Empty photo ID');
        }
    }

    /** @throws Throwable */
    private function getPhotoServer(string $accessToken): ?string
    {
        try {
            $response = $this->restServiceClient->get(
                url: $this->host . '/v1/unions/photo-server',
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

            if ($width < 400 || $height < 400) {
                return null;
            }

            return $fileName;
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }

        return null;
    }

    /** @throws Throwable */
    private function uploadPhotoFile(int $unionId, string $path, string $accessToken): bool
    {
        $photoServer = $this->getPhotoServer($accessToken);

        if (null === $photoServer) {
            return false;
        }

        try {
            $upload = $this->restServiceClient->sendFile($photoServer, $path, ['union_id' => $unionId]);
        } catch (Throwable $exception) {
            echo $exception->getMessage();
        }

        unlink($path);

        if (!isset($upload['response']['host'], $upload['response']['file_id'])) {
            return false;
        }

        $response = $this->restServiceClient->post(
            url: $this->host . '/v1/unions/' . $unionId . '/photo',
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

    private function getDirTempFiles(): string
    {
        return __DIR__ . '/../../../../var/temp';
    }
}
