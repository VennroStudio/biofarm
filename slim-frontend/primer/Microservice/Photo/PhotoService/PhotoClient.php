<?php

declare(strict_types=1);

namespace App\Components\Microservice\Photo\PhotoService;

use App\Components\Microservice\Photo\PhotoService\Responses\PhotoResult;

/**
 * Contract for photo operations used by external modules.
 *
 * Current implementation: InProcess (PhotoFacadeService in Modules/Photo).
 * After migration to microservice: HttpPhotoClient in this package.
 *
 * External modules MUST depend on this interface, not on the concrete implementation.
 */
interface PhotoClient
{
    // ── Photo creation ─────────────────────────────────────────────────

    /**
     * Create a user profile photo.
     * Resolves album, validates storage ownership, persists, publishes event.
     */
    public function saveUserProfilePhoto(int $userId, string $host, string $fileId): PhotoResult;

    /**
     * Create a union profile photo.
     */
    public function saveUnionProfilePhoto(int $userId, int $unionId, string $host, string $fileId): PhotoResult;

    /**
     * Create a post photo (user or union).
     */
    public function savePostPhoto(
        int $userId,
        ?int $unionId,
        string $host,
        string $fileId,
        ?int $createdAt = null,
    ): PhotoResult;

    /**
     * Create a message photo.
     */
    public function saveMessagePhoto(int $userId, int $conversationId, string $host, string $fileId): PhotoResult;

    /**
     * Create a comment photo (post/photo/video/flow comment).
     *
     * @param 'flow_comment'|'photo_comment'|'post_comment'|'video_comment' $commentType
     */
    public function saveCommentPhoto(
        string $commentType,
        int $userId,
        ?int $unionId,
        string $host,
        string $fileId,
    ): PhotoResult;

    // ── Photo deletion ──────────────────────────────────────────────────

    /**
     * Soft-delete a photo by ID on behalf of an actor.
     *
     * Used by external modules that need to cascade-delete photos
     * (e.g. post module deleting attached photos when a post is deleted).
     */
    public function deletePhoto(int $photoId, int $actorUserId): void;

    // ── System album provisioning ──────────────────────────────────────

    /**
     * Create system albums (profile, loaded, posts) for a new user.
     *
     * @return int Profile album ID
     */
    public function createSystemAlbumsForUser(int $userId): int;

    /**
     * Create system albums (profile, loaded, posts) for a new union.
     */
    public function createSystemAlbumsForUnion(int $unionId): void;

    // ── Queries ────────────────────────────────────────────────────────

    public function countPhotosByUser(int $userId): int;

    public function countPhotosByUnion(int $unionId): int;

    public function countAlbumsByUser(int $userId): int;

    public function countAlbumsByUnion(int $unionId): int;

    // ── Read (for external modules) ─────────────────────────────────────

    /**
     * Validate photo IDs and return only those that exist (non-deleted).
     *
     * @param int[] $ids
     * @return int[]
     */
    public function getExistingPhotoIds(array $ids): array;

    /**
     * Fetch photos by IDs and return them serialized for API responses.
     *
     * @param int[] $ids
     * @return array<int, array<string, mixed>> Serialized photos sorted by input order
     */
    public function getSerializedPhotosByIds(array $ids): array;

    /**
     * Convert raw photo sizes array into API-ready format (xs/sm/md/lg/original).
     *
     * @param array<string, mixed> $sizes
     * @return array<string, mixed>|null
     */
    public function serializePhotoSizes(array $sizes): ?array;
}
