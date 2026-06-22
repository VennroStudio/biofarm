<?php

declare(strict_types=1);

namespace App\Components\Microservice\Photo\PhotoCommentService;

use DuckBug\Duck;
use GuzzleHttp\Client;
use Override;
use Symfony\Component\Translation\Translator;
use Throwable;

/**
 * HTTP implementation of PhotoCommentsClient.
 *
 * Contract expected:
 * - GET /internal/comments/{id} -> { "data": {...} }
 */
final readonly class HttpPhotoCommentsClient implements PhotoCommentsClient
{
    public function __construct(
        private Client $client,
        private string $host,
        private string $token,
        private Duck $duck,
        private Translator $translator,
    ) {}

    #[Override]
    public function getPhotoCommentData(int $id): ?array
    {
        $result = $this->httpGet('/internal/comments/' . $id);

        $data = $this->extractData($result);

        if ($data === null) {
            $this->duck->error('HttpPhotoCommentsClient -> getPhotoCommentData()', [
                'id' => $id,
                'response' => $result,
            ]);
        }

        return $data;
    }

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
}
