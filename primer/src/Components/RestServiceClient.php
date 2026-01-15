<?php

declare(strict_types=1);

namespace App\Components;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;

final class RestServiceClient
{
    private Client $client;

    public function __construct(
    ) {
        $this->client = new Client([]);
    }

    /** @throws GuzzleException */
    public function get(string $url, array $query = [], ?string $accessToken = null): array
    {
        $headers = [];

        if (null !== $accessToken) {
            $headers['Authorization'] = $this->authorizationHeader($accessToken);
        }
        $response = $this->client->request('GET', $url, [
            'headers' => $headers,
            'query' => $query,
        ]);

        return $this->toArray($response->getBody()->getContents());
    }

    /** @throws GuzzleException */
    public function post(string $url, array $body, ?string $accessToken = null): array
    {
        $headers = [];

        if (null !== $accessToken) {
            $headers['Authorization'] = $this->authorizationHeader($accessToken);
        }

        $response = $this->client->request('POST', $url, [
            'headers' => $headers,
            'form_params' => $body,
        ]);

        return $this->toArray($response->getBody()->getContents());
    }

    /** @throws GuzzleException */
    public function put(string $url, array $body, ?string $accessToken = null): array
    {
        $headers = [];

        if (null !== $accessToken) {
            $headers['Authorization'] = $this->authorizationHeader($accessToken);
        }

        $response = $this->client->request('PUT', $url, [
            'headers' => $headers,
            'form_params' => $body,
        ]);

        return $this->toArray($response->getBody()->getContents());
    }

    /** @throws GuzzleException */
    public function delete(string $url, array $body, ?string $accessToken = null): array
    {
        $headers = [];

        if (null !== $accessToken) {
            $headers['Authorization'] = $this->authorizationHeader($accessToken);
        }

        $response = $this->client->request('DELETE', $url, [
            'headers' => $headers,
            'form_params' => $body,
        ]);

        return $this->toArray($response->getBody()->getContents());
    }

    public function postAsync(string $url, array $body, ?string $accessToken = null): void
    {
        $headers = [];

        if (null !== $accessToken) {
            $headers['Authorization'] = $this->authorizationHeader($accessToken);
        }

        $request = new Request('POST', $url);

        $this->client->sendAsync($request, [
            'headers' => $headers,
            'form_params' => $body,
        ]);
    }

    /**
     * @param array<int|string, int|string> $arr
     * @throws GuzzleException
     */
    public function sendFile(string $url, string $path, array $arr = []): array
    {
        $data = [];

        foreach ($arr as $k => $v) {
            $data[] = [
                'name'     => $k,
                'contents' => $v,
            ];
        }

        $response = $this->client->request('POST', $url, [
            RequestOptions::MULTIPART => array_merge([
                [
                    'name'     => 'upload_file',
                    'contents' => fopen($path, 'rb'),
                    'filename' => basename($path),
                ],
            ], $data),
        ]);

        return $this->toArray($response->getBody()->getContents());
    }

    private function authorizationHeader(string $accessToken): string
    {
        return 'Bearer ' . $accessToken;
    }

    private function toArray(string $json): array
    {
        try {
            return (array)json_decode($json, true);
        } catch (Exception) {
            return [];
        }
    }
}
