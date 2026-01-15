<?php

declare(strict_types=1);

namespace App\Modules\Entity\Playlist;

use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;

#[ORM\Entity]
#[ORM\Table(name: Playlist::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_URL', columns: ['url'])]
final class Playlist
{
    public const DB_NAME = 'playlists';

    public const TYPE_TIDAL = 0;
    public const TYPE_SPOTIFY = 1;
    public const TYPE_APPLE = 2;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $unionId;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $loPlaylistId = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $priority = 0;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $type;

    #[ORM\Column(type: 'string', length: 500)]
    private string $url;

    #[ORM\Column(type: 'boolean')]
    private bool $isFollowed;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $checkedAt = null;

    #[ORM\Column(type: 'integer')]
    private int $totalTracks;

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $deletedAt = null;

    /** @throws Exception */
    private function __construct(
        int $unionId,
        int $userId,
        string $name,
        string $url,
        bool $isFollowed,
    ) {
        $this->unionId = $unionId;
        $this->userId = $userId;
        $this->name = $name;
        $this->type = $this->getTypeByUrl($url);
        $this->url = $url;
        $this->isFollowed = $isFollowed;
        $this->totalTracks = 0;
        $this->createdAt = time();
    }

    public static function create(
        int $unionId,
        int $userId,
        string $name,
        string $url,
        bool $isFollowed,
    ): self {
        return new self(
            unionId: $unionId,
            userId: $userId,
            name: $name,
            url: $url,
            isFollowed: $isFollowed,
        );
    }

    public function edit(
        string $name,
        bool $isFollowed,
    ): void {
        $this->name = $name;
        $this->isFollowed = $isFollowed;
    }

    public function resetChecking(): void
    {
        $this->checkedAt = null;
    }

    public function getId(): int
    {
        if (null === $this->id) {
            throw new DomainException('Id not set');
        }
        return (int)$this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUnionId(): int
    {
        return $this->unionId;
    }

    public function setUnionId(int $unionId): void
    {
        $this->unionId = $unionId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getLoPlaylistId(): ?int
    {
        return $this->loPlaylistId;
    }

    public function setLoPlaylistId(?int $loPlaylistId): void
    {
        $this->loPlaylistId = $loPlaylistId;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function isFollowed(): bool
    {
        return $this->isFollowed;
    }

    public function setIsFollowed(bool $isFollowed): void
    {
        $this->isFollowed = $isFollowed;
    }

    public function getCheckedAt(): ?int
    {
        return $this->checkedAt;
    }

    public function setCheckedAt(?int $checkedAt): void
    {
        $this->checkedAt = $checkedAt;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getTotalTracks(): int
    {
        return $this->totalTracks;
    }

    public function setTotalTracks(int $totalTracks): void
    {
        $this->totalTracks = $totalTracks;
    }

    public function setCreatedAt(int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?int $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getDeletedAt(): ?int
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?int $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getIdByUrl(): string
    {
        $arr = explode('/', $this->url);
        return end($arr);
    }

    /** @throws Exception */
    private function getTypeByUrl(string $url): int
    {
        if (str_contains($url, 'https://listen.tidal.com/playlist/')) {
            return self::TYPE_TIDAL;
        }

        if (str_contains($url, 'https://open.spotify.com/playlist/')) {
            return self::TYPE_SPOTIFY;
        }

        if (str_contains($url, 'https://music.apple.com/')) {
            return self::TYPE_APPLE;
        }

        throw new Exception('Invalid url');
    }
}
