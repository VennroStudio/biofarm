<?php

declare(strict_types=1);

namespace App\Modules\Entity\AlbumArtist;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: AlbumArtist::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE', columns: ['album_id', 'artist_id'])]
#[ORM\Index(fields: ['artistId', 'isLoaded'], name: 'IDX_ARTIST_ID')]
final class AlbumArtist
{
    public const DB_NAME = 'album_artists';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $albumId;

    #[ORM\Column(type: 'integer')]
    private int $artistId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $number;

    #[ORM\Column(type: 'boolean')]
    private bool $isLoaded;

    private function __construct(
        int $albumId,
        int $artistId,
        ?int $number,
    ) {
        $this->albumId = $albumId;
        $this->artistId = $artistId;
        $this->number = $number;
        $this->isLoaded = false;
    }

    public static function create(
        int $albumId,
        int $artistId,
        ?int $number,
    ): self {
        return new self(
            albumId: $albumId,
            artistId: $artistId,
            number: $number
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

    public function getAlbumId(): int
    {
        return $this->albumId;
    }

    public function setAlbumId(int $albumId): void
    {
        $this->albumId = $albumId;
    }

    public function getArtistId(): int
    {
        return $this->artistId;
    }

    public function setArtistId(int $artistId): void
    {
        $this->artistId = $artistId;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(?int $number): void
    {
        $this->number = $number;
    }

    public function isLoaded(): bool
    {
        return $this->isLoaded;
    }

    public function setLoaded(): void
    {
        $this->isLoaded = true;
    }
}
