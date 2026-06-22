<?php

declare(strict_types=1);

namespace App\Components\Microservice\Contact\ContactService;

use DuckBug\Duck;
use GuzzleHttp\Client;
use Override;
use Symfony\Component\Translation\Translator;
use Throwable;

/**
 * HTTP implementation of ContactClient.
 *
 * Talks to contact-service via service-level API (X-SERVICE-TOKEN auth).
 * GET /api/v1/contacts/user/{id}/ids           -> { "data": { "ids": [...] } }
 * GET /api/v1/contacts/user/{id}/is-contact/{t} -> { "data": { "isContact": bool } }
 * GET /api/v1/contacts/user/{id}/relationship/{t} -> { "data": { "status": int } }
 */
final readonly class HttpContactClient implements ContactClient
{
    private const string API_PREFIX = '/api/v1';

    public function __construct(
        private Client $client,
        private string $host,
        private string $token,
        private Duck $duck,
        private Translator $translator,
    ) {}

    /** @return list<int> */
    #[Override]
    public function getUserContactIds(int $userId): array
    {
        $result = $this->httpGet('/contacts/user/' . $userId . '/ids');

        if ($result === null) {
            $this->duck->error('HttpContactClient -> getUserContactIds()', [
                'userId' => $userId,
            ]);
            return [];
        }

        $data = $this->extractData($result);
        $ids = $data['ids'] ?? null;
        if (!\is_array($ids)) {
            return [];
        }

        $out = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($ids as $id) {
            $intId = $this->toInt($id);
            if ($intId !== null) {
                $out[] = $intId;
            }
        }

        return $out;
    }

    #[Override]
    public function isContact(int $userId, int $contactId): bool
    {
        $result = $this->httpGet('/contacts/user/' . $userId . '/is-contact/' . $contactId);

        if ($result === null) {
            $this->duck->error('HttpContactClient -> isContact()', [
                'userId' => $userId,
                'contactId' => $contactId,
            ]);
            return false;
        }

        $data = $this->extractData($result);

        return (bool)($data['isContact'] ?? false);
    }

    #[Override]
    public function getRelationship(int $sourceId, int $targetId): int
    {
        $result = $this->httpGet('/contacts/user/' . $sourceId . '/relationship/' . $targetId);

        if ($result === null) {
            $this->duck->error('HttpContactClient -> getRelationship()', [
                'sourceId' => $sourceId,
                'targetId' => $targetId,
            ]);
            return 0;
        }

        $data = $this->extractData($result);

        return (int)($data['status'] ?? 0);
    }

    // ── HTTP helpers ────────────────────────────────────────────────────

    private function httpGet(string $uri): ?array
    {
        try {
            $response = $this->client->request('GET', $this->host . self::API_PREFIX . $uri, [
                'http_errors' => false,
                'headers' => [
                    'Accept-Language' => $this->translator->getLocale(),
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
