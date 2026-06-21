<?php

declare(strict_types=1);

namespace App\Components\YandexDisk;

use App\Components\Exception\DomainExceptionModule;
use GuzzleHttp\Client;
use Override;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final readonly class HttpYandexDiskClient implements YandexDiskClient
{
    private const string API_BASE = 'https://cloud-api.yandex.net/v1/disk/resources';

    public function __construct(
        private Client $client,
        private string $token,
    ) {}

    #[Override]
    public function upload(string $tmpFilePath, string $folder, string $fileName): string
    {
        $diskPath  = $folder . '/' . $fileName;
        $this->ensureDirectoryExists($folder, 'upload_link_failed');

        $uploadUrl = $this->getHref('/upload', ['path' => $diskPath, 'overwrite' => 'true'], 'upload_link_failed');

        $fp = fopen($tmpFilePath, 'r');
        if ($fp === false) {
            throw new DomainExceptionModule(
                module: 'yandex_disk',
                message: 'error.yandex_disk.cannot_open_file',
                code: 1,
            );
        }

        try {
            $response = $this->client->request('PUT', $uploadUrl, [
                'body'        => $fp,
                'http_errors' => false,
            ]);
        } finally {
            if (is_resource($fp)) {
                fclose($fp);
            }
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new DomainExceptionModule(
                module: 'yandex_disk',
                message: 'error.yandex_disk.upload_failed',
                code: 1,
            );
        }

        return $diskPath;
    }

    #[Override]
    public function download(string $diskPath): string
    {
        return $this->getHref('/download', ['path' => $diskPath], 'download_link_failed');
    }

    #[Override]
    public function delete(string $diskPath): void
    {
        $response = $this->request('DELETE', '', ['path' => $diskPath], 'delete_failed');
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 204 && $statusCode !== 404) {
            throw new DomainExceptionModule(
                module: 'yandex_disk',
                message: 'error.yandex_disk.delete_failed',
                code: 1,
            );
        }
    }

    private function getHref(string $uri, array $query, string $errorKey): string
    {
        $response = $this->request('GET', $uri, $query, $errorKey);

        $decoded = json_decode((string)$response->getBody(), true);

        if (!\is_array($decoded) || empty($decoded['href'])) {
            throw new DomainExceptionModule(
                module: 'yandex_disk',
                message: 'error.yandex_disk.' . $errorKey,
                code: 1,
            );
        }

        return (string)$decoded['href'];
    }

    private function ensureDirectoryExists(string $folder, string $errorKey): void
    {
        $parts = array_values(array_filter(explode('/', $folder), static fn(string $part): bool => $part !== ''));
        $path = '';

        foreach ($parts as $part) {
            $path = $path === '' ? $part : $path . '/' . $part;

            try {
                $response = $this->client->request('PUT', self::API_BASE, [
                    'query'       => ['path' => $path],
                    'http_errors' => false,
                    'headers'     => $this->authHeaders(),
                ]);
            } catch (Throwable) {
                throw new DomainExceptionModule(
                    module: 'yandex_disk',
                    message: 'error.yandex_disk.' . $errorKey,
                    code: 1,
                );
            }

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 201 && $statusCode !== 409) {
                throw new DomainExceptionModule(
                    module: 'yandex_disk',
                    message: 'error.yandex_disk.' . $errorKey,
                    code: 1,
                );
            }
        }
    }

    private function request(string $method, string $uri, array $query, string $errorKey): ResponseInterface
    {
        try {
            $response = $this->client->request($method, self::API_BASE . $uri, [
                'query'       => $query,
                'http_errors' => false,
                'headers'     => $this->authHeaders(),
            ]);

            if ($response->getStatusCode() >= 400) {
                throw new DomainExceptionModule(
                    module: 'yandex_disk',
                    message: 'error.yandex_disk.' . $errorKey,
                    code: 1,
                );
            }

            return $response;
        } catch (DomainExceptionModule $e) {
            throw $e;
        } catch (Throwable) {
            throw new DomainExceptionModule(
                module: 'yandex_disk',
                message: 'error.yandex_disk.' . $errorKey,
                code: 1,
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        return ['Authorization' => 'OAuth ' . $this->token];
    }
}
