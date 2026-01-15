<?php

declare(strict_types=1);

namespace App\Modules\Entity\AppleAlbumArtist;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: AppleAlbumArtist::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE', columns: ['album_id', 'artist_id'])]
#[ORM\Index(fields: ['artistId'], name: 'IDX_ARTIST_ID')]
final class AppleAlbumArtist
{
    public const DB_NAME = 'apple_album_artists';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $albumId;

    #[ORM\Column(type: 'integer')]
    private int $artistId;

    private function __construct(
        int $albumId,
        int $artistId,
    ) {
        $this->albumId = $albumId;
        $this->artistId = $artistId;
    }

    public static function create(
        int $albumId,
        int $artistId,
    ): self {
        return new self(
            albumId: $albumId,
            artistId: $artistId,
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
}
