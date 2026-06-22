<?php

declare(strict_types=1);

namespace App\Components\Microservice;

use DuckBug\Duck;
use GuzzleHttp\Client;
use Symfony\Component\Translation\Translator;
use Throwable;

abstract readonly class MicroserviceClient
{
    public function __construct(
        private Client $client,
        private string $host,
        protected Duck $duck,
        private Translator $translator
    ) {}

    protected function get(string $uri, array $query = [], array $headers = []): ?array
    {
        try {
            $response = $this->client->request('GET', $this->host . $uri, [
                'query' => $query,
                'http_errors' => false,
                'headers' => [
                    'Accept-Language' => $this->translator->getLocale(),
                    ...$headers,
                ],
            ]);

            $body = (string)$response->getBody();

            /** @var mixed $data */
            $data = json_decode($body, true);

            return \is_array($data) ? $data : null;
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    protected function post(string $uri, array $data = [], array $query = [], array $headers = []): ?array
    {
        try {
            $options = [
                'query' => $query,
                'http_errors' => false,
                'headers' => [
                    'Accept' => 'application/json',
                    'Accept-Language' => $this->translator->getLocale(),
                    ...$headers,
                ],
            ];

            if (!empty($data)) {
                $options['json'] = $data;
                $options['headers']['Content-Type'] = 'application/json';
            }

            $response = $this->client->request('POST', $this->host . $uri, $options);

            $body = (string)$response->getBody();

            /** @var mixed $data */
            $data = json_decode($body, true);

            return \is_array($data) ? $data : null;
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    /**
     * @param string[] $path
     */
    protected function extract(mixed $data, array $path = ['data'], bool $isArray = false): ?array
    {
        foreach ($path as $key) {
            if (!\is_array($data) || !isset($data[$key]) || !\is_array($data[$key])) {
                return $isArray ? [] : null;
            }
            $data = $data[$key];
        }
        return (array)$data;
    }

    protected function extractDataList(mixed $data): array
    {
        return $this->extract($data, ['data'], true) ?? [];
    }

    protected function extractItems(mixed $data): array
    {
        return $this->extract($data, ['data', 'items'], true) ?? [];
    }
}
