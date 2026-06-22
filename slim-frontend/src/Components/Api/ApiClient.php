<?php

declare(strict_types=1);

namespace App\Components\Api;

use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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
        return $this->request('GET', $path, [
            'query' => $query,
        ]);
    }

    /**
     * @param array<string, array<string, bool|float|int|string>|bool|float|int|string> $json
     * @return array<array-key, mixed>
     */
    public function post(string $path, array $json = []): array
    {
        return $this->request('POST', $path, [
            'json' => $json,
        ]);
    }

    /**
     * @param array<string, array<string, bool|float|int|string>|bool|float|int|string> $json
     * @return array<array-key, mixed>
     */
    public function patch(string $path, array $json = []): array
    {
        return $this->request('PATCH', $path, [
            'json' => $json,
        ]);
    }

    /**
     * @param array<string, bool|float|int|string> $json
     * @return array<array-key, mixed>
     */
    public function delete(string $path, array $json = []): array
    {
        return $this->request('DELETE', $path, [
            'json' => $json,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<array-key, mixed>
     */
    private function request(string $method, string $path, array $options): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $exception) {
            throw ApiException::transportFailed($method, $exception);
        }

        if ($statusCode >= 400) {
            throw ApiException::requestFailed($method, $statusCode);
        }

        try {
            return $response->toArray(false);
        } catch (DecodingExceptionInterface $exception) {
            throw ApiException::invalidResponse($exception);
        } catch (TransportExceptionInterface $exception) {
            throw ApiException::transportFailed($method, $exception);
        } catch (ExceptionInterface $exception) {
            throw new ApiException($exception->getMessage(), 0, $exception);
        }
    }
}
