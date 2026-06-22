<?php

declare(strict_types=1);

namespace App\Components\Microservice\Flow\FlowService;

use DuckBug\Duck;
use GuzzleHttp\Client;
use Override;
use Symfony\Component\Translation\Translator;
use Throwable;

/**
 * HTTP implementation of FlowClient.
 *
 * Talks to flows-service internal API.
 * Used when Flow module runs as a separate microservice.
 *
 * Contract expected:
 * - GET /internal/flows?ids=1,2,3           -> { "data": { "items": [...] } }
 * - GET /internal/flows/{id}                -> { "data": {...} }
 */
final readonly class HttpFlowClient implements FlowClient
{
    public function __construct(
        private Client $client,
        private string $host,
        private string $token,
        private Duck $duck,
        private Translator $translator,
    ) {}

    #[Override]
    public function getSerializedFlowsByIds(array $ids): array
    {
        $ids = array_values(array_filter($ids, \is_int(...)));
        /** @var list<int> $ids */
        if ($ids === []) {
            return [];
        }

        $result = $this->httpGet('/internal/flows', ['ids' => implode(',', $ids)]);

        $items = $this->extractItems($result);

        if ($items === []) {
            $this->duck->error('HttpFlowClient -> getSerializedFlowsByIds()', [
                'ids' => $ids,
                'response' => $result,
            ]);
        }

        return $items;
    }

    #[Override]
    public function getFlowData(int $id): ?array
    {
        $result = $this->httpGet('/internal/flows/' . $id);

        $data = $this->extractData($result);

        if ($data === null) {
            $this->duck->error('HttpFlowClient -> getFlowData()', [
                'id' => $id,
                'response' => $result,
            ]);
        }

        return $data;
    }

    // ── HTTP helpers ────────────────────────────────────────────────────

    private function httpGet(string $uri, array $query = []): ?array
    {
        try {
            $response = $this->client->request('GET', $this->host . $uri, [
                'query' => $query,
                'http_errors' => false,
                'headers' => [
                    'Accept-Language' => $this->translator->getLocale(),
                    'X-SERVICE-TOKEN' => $this->token,
                ],
            ]);

            /** @var mixed $data */
            $data = json_decode((string)$response->getBody(), true);

            return \is_array($data) ? $data : null;
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

    /**
     * @return list<array<array-key, mixed>>
     */
    private function extractItems(?array $response): array
    {
        $items = $response['data']['items'] ?? null;

        if (!\is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, static fn (mixed $item): bool => \is_array($item)));
    }
}
