<?php

declare(strict_types=1);

namespace App\Components\Microservice\Moderation\ModerationService;

use DuckBug\Duck;
use GuzzleHttp\Client;
use Override;
use Throwable;

/**
 * HTTP implementation of ModerationClient.
 *
 * Talks to moderation-service API. Host in config is base URL without path (e.g. http://moderation-service:8080).
 * GET /api/v1/shadowban/owner-ids -> { "data": { "ids": [...] } }
 */
final readonly class HttpModerationClient implements ModerationClient
{
    private const string API_PREFIX = '/api/v1';

    public function __construct(
        private Client $client,
        private string $host,
        private string $token,
        private Duck $duck,
    ) {}

    /** @return list<int> */
    #[Override]
    public function getShadowBanOwnerIds(): array
    {
        $result = $this->httpGet('/shadowban/owner-ids');

        $data = $this->extractData($result);
        if (!\is_array($data)) {
            $this->duck->error('HttpModerationClient -> getShadowBanOwnerIds()', [
                'response' => $result,
            ]);
            return [];
        }

        $ids = $data['ids'] ?? null;
        if (!\is_array($ids)) {
            return [];
        }

        /** @var list<int|string> $ids */
        $out = [];
        foreach ($ids as $id) {
            $intId = $this->toInt($id);
            if ($intId !== null) {
                $out[] = $intId;
            }
        }

        return $out;
    }

    #[Override]
    public function isIpBlacklisted(string $ipAddress): bool
    {
        $result = $this->httpGet('/ip-blacklist/check?ip=' . rawurlencode($ipAddress));
        $data = $this->extractData($result);
        if (!\is_array($data) || !isset($data['blacklisted'])) {
            $this->duck->error('HttpModerationClient -> isIpBlacklisted()', [
                'response' => $result,
            ]);
            return false;
        }

        return (bool)$data['blacklisted'];
    }

    private function httpGet(string $uri): ?array
    {
        try {
            $url = $this->host . self::API_PREFIX . $uri;
            $response = $this->client->request('GET', $url, [
                'http_errors' => false,
                'headers' => [
                    'X-SERVICE-TOKEN' => $this->token,
                ],
            ]);

            /** @var mixed $decoded */
            $decoded = json_decode((string)$response->getBody(), true);

            return \is_array($decoded) ? $decoded : null;
        } catch (Throwable $e) {
            $this->duck->quack($e);

            return null;
        }
    }

    private function extractData(?array $response): ?array
    {
        if (!\is_array($response) || !isset($response['data']) || !\is_array($response['data'])) {
            return null;
        }

        return $response['data'];
    }

    private function toInt(mixed $value): ?int
    {
        if (\is_int($value)) {
            return $value;
        }
        if (\is_string($value) && is_numeric($value)) {
            return (int)$value;
        }

        return null;
    }
}
