<?php

declare(strict_types=1);

namespace App\Components\Microservice\Storage\StorageService;

use App\Components\Http\Exception\DomainExceptionModule;
use App\Components\Microservice\Storage\StorageService\DTO\StorageHostDTO;
use DuckBug\Duck;
use GuzzleHttp\Client;
use Override;
use Throwable;

final readonly class HttpStorageHostClient implements StorageHostClient
{
    private const string API_PREFIX = '/api/v1';

    public function __construct(
        private Client $client,
        private string $host,
        private string $token,
        private Duck $duck,
    ) {}

    #[Override]
    public function getByRandom(?string $capability = null): StorageHostDTO
    {
        $query = [];
        if ($capability !== null) {
            $query['capability'] = $capability;
        }

        $data = $this->request('/hosts/random', $query);

        if ($data === null) {
            throw new DomainExceptionModule(
                module: 'storage',
                message: 'error.storage.storage_not_available',
                code: 1
            );
        }

        return $this->mapToDTO($data);
    }

    #[Override]
    public function getByHost(string $host): StorageHostDTO
    {
        $data = $this->request('/hosts/by-name', ['host' => $host]);

        if ($data === null) {
            throw new DomainExceptionModule(
                module: 'storage',
                message: 'error.storage.storage_not_found',
                code: 1
            );
        }

        return $this->mapToDTO($data);
    }

    #[Override]
    public function getUploadURL(string $type, ?string $region = null, ?string $fileId = null): string
    {
        $query = ['type' => $type];
        if ($region !== null) {
            $query['region'] = $region;
        }
        if ($fileId !== null) {
            $query['fileId'] = $fileId;
        }

        $data = $this->request('/upload-url', $query);

        if ($data === null || !isset($data['url'])) {
            throw new DomainExceptionModule(
                module: 'storage',
                message: 'error.storage.storage_not_available',
                code: 1
            );
        }

        return (string)$data['url'];
    }

    private function request(string $uri, array $query = []): ?array
    {
        try {
            $url = $this->host . self::API_PREFIX . $uri;
            $response = $this->client->request('GET', $url, [
                'query' => $query,
                'http_errors' => false,
                'headers' => [
                    'X-SERVICE-TOKEN' => $this->token,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                $this->duck->error('HttpStorageHostClient -> request()', [
                    'uri' => $uri,
                    'query' => $query,
                    'statusCode' => $statusCode,
                    'body' => (string)$response->getBody(),
                ]);
                return null;
            }

            /** @var mixed $decoded */
            $decoded = json_decode((string)$response->getBody(), true);
            if (!\is_array($decoded) || !isset($decoded['data']) || !\is_array($decoded['data'])) {
                return null;
            }

            return $decoded['data'];
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    private function mapToDTO(array $data): StorageHostDTO
    {
        return new StorageHostDTO(
            id: (int)($data['id'] ?? 0),
            host: (string)($data['host'] ?? ''),
            secret: (string)($data['secret'] ?? ''),
            region: (string)($data['region'] ?? ''),
            weight: (int)($data['weight'] ?? 0),
            capabilities: (string)($data['capabilities'] ?? ''),
            isActive: (bool)($data['isActive'] ?? false),
        );
    }
}
