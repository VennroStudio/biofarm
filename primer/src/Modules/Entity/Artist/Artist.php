<?php

declare(strict_types=1);

namespace App\Modules\Entity\Artist;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: Artist::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_UNION', columns: ['union_id'])]
#[ORM\Index(fields: ['mergedAt'], name: 'IDX_MERGED_AT')]
#[ORM\Index(fields: ['priority'], name: 'IDX_PRIORITY')]
final class Artist
{
    public const DB_NAME = 'artists';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $unionId;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $description;

    #[ORM\Column(type: 'string', length: 255)]
    private string $loName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $loDescription;

    #[ORM\Column(type: 'integer')]
    private int $loCategoryId;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $priority = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $spotifyCheckedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tidalCheckedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $appleCheckedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $checkedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mergedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rateCheckedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $similarIds = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $popularTrackIds = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $synchronizedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isAutomatic;

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $deletedAt = null;

    private function __construct(
        int $unionId,
        int $userId,
        string $description,
        string $loName,
        string $loDescription,
        int $loCategoryId,
        bool $isAutomatic,
    ) {
        $this->unionId = $unionId;
        $this->userId = $userId;
        $this->description = $description;
        $this->loName = $loName;
        $this->loDescription = $loDescription;
        $this->loCategoryId = $loCategoryId;
        $this->isAutomatic = $isAutomatic;
        $this->createdAt = time();
    }

    public static function create(
        int $unionId,
        int $userId,
        string $description,
        string $loName,
        string $loDescription,
        int $loCategoryId,
        bool $isAutomatic,
    ): self {
        return new self(
            unionId: $unionId,
            userId: $userId,
            description: $description,
            loName: $loName,
            loDescription: $loDescription,
            loCategoryId: $loCategoryId,
            isAutomatic: $isAutomatic,
        );
    }

    public function edit(
        string $description,
        string $loName,
        string $loDescription,
        int $loCategoryId,
    ): void {
        $this->description = $description;
        $this->loName = $loName;
        $this->loDescription = $loDescription;
        $this->loCategoryId = $loCategoryId;
    }

    public function resetChecking(): void
    {
        $this->spotifyCheckedAt = -1 * time();
        $this->resetCheckingFromTidal();
    }

    public function resetCheckingFromTidal(): void
    {
        $this->tidalCheckedAt   = null;
        $this->appleCheckedAt   = null;
        $this->mergedAt         = null;
        $this->checkedAt        = null;
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getLoName(): string
    {
        return $this->loName;
    }

    public function setLoName(string $loName): void
    {
        $this->loName = $loName;
    }

    public function getLoDescription(): string
    {
        return $this->loDescription;
    }

    public function setLoDescription(string $loDescription): void
    {
        $this->loDescription = $loDescription;
    }

    public function getLoCategoryId(): int
    {
        return $this->loCategoryId;
    }

    public function setLoCategoryId(int $loCategoryId): void
    {
        $this->loCategoryId = $loCategoryId;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getSpotifyCheckedAt(): ?int
    {
        return $this->spotifyCheckedAt;
    }

    public function setSpotifyChecked(): void
    {
        $this->spotifyCheckedAt = time();
    }

    public function getTidalCheckedAt(): ?int
    {
        return $this->tidalCheckedAt;
    }

    public function setTidalChecked(): void
    {
        $this->tidalCheckedAt = time();
    }

    public function getAppleCheckedAt(): ?int
    {
        return $this->appleCheckedAt;
    }

    public function setAppleChecked(): void
    {
        $this->appleCheckedAt = time();
    }

    public function getCheckedAt(): ?int
    {
        return $this->checkedAt;
    }

    public function setChecked(): void
    {
        $this->checkedAt = time();
    }

    public function getMergedAt(): ?int
    {
        return $this->mergedAt;
    }

    public function setMerged(): void
    {
        $this->mergedAt = time();
    }

    public function getRateCheckedAt(): ?int
    {
        return $this->rateCheckedAt;
    }

    public function setRateChecked(): void
    {
        $this->rateCheckedAt = time();
    }

    public function getSimilarIds(): ?string
    {
        return $this->similarIds;
    }

    public function setSimilarIds(?string $similarIds): void
    {
        $this->similarIds = $similarIds;
    }

    public function getPopularTrackIds(): ?string
    {
        return $this->popularTrackIds;
    }

    public function setPopularTrackIds(?string $popularTrackIds): void
    {
        $this->popularTrackIds = $popularTrackIds;
    }

    public function getSynchronizedAt(): ?int
    {
        return $this->synchronizedAt;
    }

    public function setSynchronized(): void
    {
        $this->synchronizedAt = time();
    }

    public function isAutomatic(): bool
    {
        return $this->isAutomatic;
    }

    public function setIsAutomatic(bool $isAutomatic): void
    {
        $this->isAutomatic = $isAutomatic;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
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
}
