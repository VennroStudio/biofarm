<?php

declare(strict_types=1);

namespace App\Components\Integration\Bitrix24;

use GuzzleHttp\Client;
use JsonException;
use Throwable;

final readonly class Bitrix24Client
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout'         => 8,
            'connect_timeout' => 4,
            'http_errors'     => false,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function call(string $webhookUrl, string $method, array $payload = []): array
    {
        $url = $this->methodUrl($webhookUrl, $method);

        try {
            $response = $this->client->post($url, ['json' => $payload]);
        } catch (Throwable $exception) {
            throw new Bitrix24Exception($exception->getMessage());
        }

        $status = $response->getStatusCode();
        $body = (string)$response->getBody();

        try {
            $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new Bitrix24Exception('Bitrix24 returned a non-JSON response.', $status, $body);
        }

        if (!\is_array($decoded)) {
            throw new Bitrix24Exception('Bitrix24 returned an invalid response.', $status, $body);
        }

        if ($status >= 400 || isset($decoded['error'])) {
            $message = \is_string($decoded['error_description'] ?? null)
                ? $decoded['error_description']
                : (string)($decoded['error'] ?? 'Bitrix24 request failed.');

            throw new Bitrix24Exception($message, $status, $body);
        }

        return $decoded;
    }

    private function methodUrl(string $webhookUrl, string $method): string
    {
        $baseUrl = rtrim($webhookUrl, '/');
        $suffix = str_ends_with($method, '.json') ? $method : $method . '.json';

        return $baseUrl . '/' . $suffix;
    }
}
