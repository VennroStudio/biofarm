<?php

declare(strict_types=1);

namespace App\Components\Microservice\Photo\PhotoService\Responses;

/**
 * Immutable DTO returned by PhotoClient after creating a photo.
 *
 * Lives outside the Photo module — survives microservice migration.
 * When the Photo module becomes a microservice, this DTO will be
 * constructed from the HTTP API response instead of the entity.
 */
final readonly class PhotoResult
{
    public function __construct(
        public int $id,
        public int $type,
        public ?int $albumId,
        public int $userId,
        public ?int $unionId,
        public int $ownerId,
        public ?string $photoJson,
        public ?string $photoHost,
        public ?string $photoFileId,
        public ?string $description,
        public int $createdAt,
        public ?int $updatedAt,
        public int $countLikes,
        public int $countComments,
    ) {}

    /**
     * Array representation compatible with Photo::toArray().
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'type'              => $this->type,
            'album_id'          => $this->albumId,
            'user_id'           => $this->userId,
            'union_id'          => $this->unionId,
            'owner_id'          => $this->ownerId,
            'photo'             => $this->photoJson,
            'photo_host'        => $this->photoHost,
            'photo_file_id'     => $this->photoFileId,
            'description'       => $this->description,
            'created_at'        => $this->createdAt,
            'updated_at'        => $this->updatedAt,
            'count_likes'       => $this->countLikes,
            'count_comments'    => $this->countComments,
        ];
    }
}
