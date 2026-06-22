<?php

declare(strict_types=1);

namespace App\Components\Api;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ApiClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
    ) {}

    /**
     * @param array<string, bool|float|int|string> $query
     * @return array<array-key, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $method = 'GET';
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $response = $this->httpClient->request($method, $url, [
            'query' => $query,
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            throw ApiException::requestFailed($method, $url, $statusCode);
        }

        try {
            return $response->toArray(false);
        } catch (ExceptionInterface $exception) {
            throw new ApiException($exception->getMessage(), 0, $exception);
        }
    }
}
