<?php

declare(strict_types=1);

namespace App\Components\Microservice\Photo\PhotoService;

use App\Components\Microservice\Photo\PhotoService\Responses\PhotoResult;
use DuckBug\Duck;
use GuzzleHttp\Client;
use Override;
use RuntimeException;
use Symfony\Component\Translation\Translator;
use Throwable;

/**
 * HTTP implementation of PhotoClient.
 *
 * Talks to photos-service internal API.
 * Used when Photo module runs as a separate microservice.
 *
 * Contracts (see wiki/photo-migration/*):
 * - POST /internal/photos/save-user-profile
 * - POST /internal/photos/save-union-profile
 * - POST /internal/photos/save-post
 * - POST /internal/photos/save-message
 * - POST /internal/photos/save-comment
 * - DELETE /internal/photos/{id}
 * - POST /internal/photos/system-albums/user
 * - POST /internal/photos/system-albums/union
 * - POST /internal/photos/existing-ids
 * - POST /internal/photos/count
 * - POST /internal/photos/albums/count
 * - POST /internal/resolve/photos
 */
final readonly class HttpPhotoClient implements PhotoClient
{
    public function __construct(
        private Client $client,
        private string $host,
        private string $token,
        private Duck $duck,
        private Translator $translator,
    ) {}

    #[Override]
    public function saveUserProfilePhoto(int $userId, string $host, string $fileId): PhotoResult
    {
        return $this->save('/internal/photos/save-user-profile', [
            'userId' => $userId,
            'host' => $host,
            'fileId' => $fileId,
        ]);
    }

    #[Override]
    public function saveUnionProfilePhoto(int $userId, int $unionId, string $host, string $fileId): PhotoResult
    {
        return $this->save('/internal/photos/save-union-profile', [
            'userId' => $userId,
            'unionId' => $unionId,
            'host' => $host,
            'fileId' => $fileId,
        ]);
    }

    #[Override]
    public function savePostPhoto(int $userId, ?int $unionId, string $host, string $fileId, ?int $createdAt = null): PhotoResult
    {
        $payload = [
            'userId' => $userId,
            'host' => $host,
            'fileId' => $fileId,
        ];

        if ($unionId !== null) {
            $payload['unionId'] = $unionId;
        }

        if ($createdAt !== null) {
            $payload['createdAt'] = $createdAt;
        }

        return $this->save('/internal/photos/save-post', $payload);
    }

    #[Override]
    public function saveMessagePhoto(int $userId, int $conversationId, string $host, string $fileId): PhotoResult
    {
        return $this->save('/internal/photos/save-message', [
            'userId' => $userId,
            'conversationId' => $conversationId,
            'host' => $host,
            'fileId' => $fileId,
        ]);
    }

    #[Override]
    public function saveCommentPhoto(string $commentType, int $userId, ?int $unionId, string $host, string $fileId): PhotoResult
    {
        $payload = [
            'commentType' => $commentType,
            'userId' => $userId,
            'host' => $host,
            'fileId' => $fileId,
        ];

        if ($unionId !== null) {
            $payload['unionId'] = $unionId;
        }

        return $this->save('/internal/photos/save-comment', $payload);
    }

    #[Override]
    public function deletePhoto(int $photoId, int $actorUserId): void
    {
        $result = $this->httpDelete('/internal/photos/' . $photoId, [
            'actorUserId' => $actorUserId,
        ]);

        if ($result === null) {
            $this->duck->error('HttpPhotoClient -> deletePhoto()', [
                'photoId' => $photoId,
                'actorUserId' => $actorUserId,
            ]);
        }
    }

    #[Override]
    public function createSystemAlbumsForUser(int $userId): int
    {
        $result = $this->httpPost('/internal/photos/system-albums/user', [
            'userId' => $userId,
        ]);

        $data = $this->extractData($result);
        $albumId = $this->toInt($data['profileAlbumId'] ?? $data['profile_album_id'] ?? null);

        if ($albumId === null) {
            $this->duck->error('HttpPhotoClient -> createSystemAlbumsForUser()', [
                'userId' => $userId,
                'response' => $result,
            ]);
            return 0;
        }

        return $albumId;
    }

    #[Override]
    public function createSystemAlbumsForUnion(int $unionId): void
    {
        $result = $this->httpPost('/internal/photos/system-albums/union', [
            'unionId' => $unionId,
        ]);

        if ($result === null) {
            $this->duck->error('HttpPhotoClient -> createSystemAlbumsForUnion()', [
                'unionId' => $unionId,
            ]);
        }
    }

    #[Override]
    public function countPhotosByUser(int $userId): int
    {
        return $this->extractCount(
            $this->httpPost('/internal/photos/count', ['userId' => $userId]),
            __FUNCTION__,
            ['userId' => $userId]
        );
    }

    #[Override]
    public function countPhotosByUnion(int $unionId): int
    {
        return $this->extractCount(
            $this->httpPost('/internal/photos/count', ['unionId' => $unionId]),
            __FUNCTION__,
            ['unionId' => $unionId]
        );
    }

    #[Override]
    public function countAlbumsByUser(int $userId): int
    {
        return $this->extractCount(
            $this->httpPost('/internal/photos/albums/count', ['userId' => $userId]),
            __FUNCTION__,
            ['userId' => $userId]
        );
    }

    #[Override]
    public function countAlbumsByUnion(int $unionId): int
    {
        return $this->extractCount(
            $this->httpPost('/internal/photos/albums/count', ['unionId' => $unionId]),
            __FUNCTION__,
            ['unionId' => $unionId]
        );
    }

    #[Override]
    public function getExistingPhotoIds(array $ids): array
    {
        $ids = array_values(array_filter($ids, \is_int(...)));

        if ($ids === []) {
            return [];
        }

        $result = $this->httpPost('/internal/photos/existing-ids', [
            'ids' => $ids,
        ]);

        $data = $this->extractData($result);

        $resultIds = $data['ids'] ?? $result['ids'] ?? null;
        if (!\is_array($resultIds)) {
            $this->duck->error('HttpPhotoClient -> getExistingPhotoIds()', [
                'ids' => $ids,
                'response' => $result,
            ]);
            return [];
        }

        $out = [];
        foreach (array_keys($resultIds) as $key) {
            $id = $this->toInt($resultIds[$key] ?? null);
            if ($id !== null) {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }

    #[Override]
    public function getSerializedPhotosByIds(array $ids): array
    {
        $ids = array_values(array_filter($ids, \is_int(...)));

        if ($ids === []) {
            return [];
        }

        $result = $this->httpPost('/internal/resolve/photos', [
            'ids' => $ids,
        ]);

        $items = $this->extractItems($result);

        if ($items === []) {
            $this->duck->error('HttpPhotoClient -> getSerializedPhotosByIds()', [
                'ids' => $ids,
                'response' => $result,
            ]);
        }

        return $items;
    }

    #[Override]
    public function serializePhotoSizes(array $sizes): ?array
    {
        if ($sizes === []) {
            return null;
        }

        ksort($sizes);

        foreach (['crop_square', 'crop_custom'] as $key) {
            if (\array_key_exists($key, $sizes)) {
                unset($sizes[$key]);
            }
        }

        /** @var array<array-key, mixed>|string|null $original */
        $original = null;
        if (\array_key_exists('original', $sizes)) {
            if (\is_array($sizes['original']) || \is_string($sizes['original'])) {
                $original = $sizes['original'];
            }
        }

        $cropSizes = array_values(
            array_filter(
                array_values($sizes),
                static fn (mixed $value): bool => \is_array($value)
            )
        );

        if ($cropSizes === [] && $original === null) {
            return null;
        }

        return [
            'xs' => $cropSizes[0] ?? $cropSizes[1] ?? $cropSizes[2] ?? $cropSizes[3] ?? $original,
            'sm' => $cropSizes[1] ?? $cropSizes[2] ?? $cropSizes[3] ?? $original,
            'md' => $cropSizes[2] ?? $cropSizes[3] ?? $original,
            'lg' => $cropSizes[3] ?? $cropSizes[2] ?? $original,
            'original' => $original,
        ];
    }

    // ── Save helpers ────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $payload
     */
    private function save(string $uri, array $payload): PhotoResult
    {
        $result = $this->httpPost($uri, $payload);
        $data = $this->extractData($result) ?? $result;

        if (!\is_array($data)) {
            $this->duck->error('HttpPhotoClient -> save()', [
                'uri' => $uri,
                'payload' => $payload,
                'response' => $result,
            ]);
            throw new RuntimeException('photos-service returned invalid response');
        }

        return $this->toPhotoResult($data);
    }

    private function extractCount(?array $response, string $method, array $context): int
    {
        $data = $this->extractData($response) ?? $response;
        $count = $this->toInt(\is_array($data) ? ($data['count'] ?? null) : null);

        if ($count === null) {
            $this->duck->error('HttpPhotoClient -> ' . $method . '()', [
                ...$context,
                'response' => $response,
            ]);
            return 0;
        }

        return $count;
    }

    // ── HTTP helpers ────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $payload
     */
    private function httpPost(string $uri, array $payload, array $query = []): ?array
    {
        return $this->httpRequest('POST', $uri, [
            'json' => $payload,
            'query' => $query,
        ]);
    }

    private function httpDelete(string $uri, array $payload = []): ?array
    {
        $options = [];
        if ($payload !== []) {
            $options['json'] = $payload;
        }

        return $this->httpRequest('DELETE', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function httpRequest(string $method, string $uri, array $options = []): ?array
    {
        try {
            $response = $this->client->request($method, $this->host . $uri, [
                ...$options,
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
     * @return list<array<string, mixed>>
     */
    private function extractItems(?array $response): array
    {
        $data = $this->extractData($response) ?? $response;
        if (!\is_array($data)) {
            return [];
        }

        $items = $data['items'] ?? null;
        if (!\is_array($items)) {
            return [];
        }

        /** @var list<array<string, mixed>> $out */
        $out = array_values(array_filter($items, static fn (mixed $item): bool => \is_array($item)));

        return $out;
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

    /**
     * @param array<array-key, mixed> $data
     */
    private function toPhotoResult(array $data): PhotoResult
    {
        $id = $this->toInt($data['id'] ?? null);
        $type = $this->toInt($data['type'] ?? null);

        $albumId = $this->toInt($data['albumId'] ?? $data['album_id'] ?? null);
        $userId = $this->toInt($data['userId'] ?? $data['user_id'] ?? null);
        $unionId = $this->toInt($data['unionId'] ?? $data['union_id'] ?? null);
        $ownerId = $this->toInt($data['ownerId'] ?? $data['owner_id'] ?? null);

        if ($ownerId === null) {
            if ($unionId !== null) {
                $ownerId = -1 * $unionId;
            } elseif ($userId !== null) {
                $ownerId = $userId;
            }
        }

        $createdAt = $this->toInt($data['createdAt'] ?? $data['created_at'] ?? null);
        $updatedAt = $this->toInt($data['updatedAt'] ?? $data['updated_at'] ?? null);

        $countLikes = $this->toInt($data['countLikes'] ?? $data['count_likes'] ?? null) ?? 0;
        $countComments = $this->toInt($data['countComments'] ?? $data['count_comments'] ?? null) ?? 0;

        $photoJson = null;
        if (isset($data['photo']) && \is_string($data['photo'])) {
            $photoJson = $data['photo'];
        } elseif (isset($data['photoJson']) && \is_string($data['photoJson'])) {
            $photoJson = $data['photoJson'];
        }

        $photoHost = null;
        if (isset($data['photoHost']) && \is_string($data['photoHost'])) {
            $photoHost = $data['photoHost'];
        } elseif (isset($data['photo_host']) && \is_string($data['photo_host'])) {
            $photoHost = $data['photo_host'];
        }

        $photoFileId = null;
        if (isset($data['photoFileId']) && \is_string($data['photoFileId'])) {
            $photoFileId = $data['photoFileId'];
        } elseif (isset($data['photo_file_id']) && \is_string($data['photo_file_id'])) {
            $photoFileId = $data['photo_file_id'];
        }

        $description = null;
        if (isset($data['description']) && \is_string($data['description'])) {
            $description = $data['description'];
        }

        if ($id === null || $type === null || $userId === null || $ownerId === null || $createdAt === null) {
            $this->duck->error('HttpPhotoClient -> toPhotoResult(): invalid photo payload', [
                'payload' => $data,
            ]);
            throw new RuntimeException('photos-service returned invalid photo payload');
        }

        return new PhotoResult(
            id: $id,
            type: $type,
            albumId: $albumId,
            userId: $userId,
            unionId: $unionId,
            ownerId: $ownerId,
            photoJson: $photoJson,
            photoHost: $photoHost,
            photoFileId: $photoFileId,
            description: $description,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            countLikes: $countLikes,
            countComments: $countComments,
        );
    }
}
