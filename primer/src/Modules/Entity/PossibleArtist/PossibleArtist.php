<?php

declare(strict_types=1);

namespace App\Modules\Entity\PossibleArtist;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: PossibleArtist::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_ARTIST', columns: ['artist_id'])]
final class PossibleArtist
{
    public const DB_NAME = 'possibly_artists';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $artistId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $playlistId;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $spotifyId;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $appleId;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $tidalId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $checkedAt = null;

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $deletedAt = null;

    private function __construct(
        string $name,
        ?int $playlistId,
        ?string $spotifyId,
        ?string $appleId,
        ?string $tidalId
    ) {
        $this->name = $name;
        $this->playlistId = $playlistId;
        $this->spotifyId = $spotifyId;
        $this->appleId = $appleId;
        $this->tidalId = $tidalId;
        $this->createdAt = time();
    }

    public static function create(
        string $name,
        ?int $playlistId,
        ?string $spotifyId,
        ?string $appleId,
        ?string $tidalId
    ): self {
        return new self(
            name: $name,
            playlistId: $playlistId,
            spotifyId: $spotifyId,
            appleId: $appleId,
            tidalId: $tidalId,
        );
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getArtistId(): ?int
    {
        return $this->artistId;
    }

    public function setArtistId(?int $artistId): void
    {
        $this->artistId = $artistId;
    }

    public function getPlaylistId(): ?int
    {
        return $this->playlistId;
    }

    public function setPlaylistId(?int $playlistId): void
    {
        $this->playlistId = $playlistId;
    }

    public function getSpotifyId(): ?string
    {
        return $this->spotifyId;
    }

    public function setSpotifyId(?string $spotifyId): void
    {
        $this->spotifyId = $spotifyId;
    }

    public function getAppleId(): ?string
    {
        return $this->appleId;
    }

    public function setAppleId(?string $appleId): void
    {
        $this->appleId = $appleId;
    }

    public function getTidalId(): ?string
    {
        return $this->tidalId;
    }

    public function setTidalId(?string $tidalId): void
    {
        $this->tidalId = $tidalId;
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
