<?php

declare(strict_types=1);

namespace App\Modules\Entity\ArtistProblematic;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: 'artist_problematic')]
class ArtistProblematic
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $artistId;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $artistName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $tidalUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $spotifyUrl = null;

    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $status = 0;

    private function __construct(
        int $artistId,
        string $artistName,
    ) {
        $this->artistId = $artistId;
        $this->artistName = $artistName;
    }

    public static function create(
        int $artistId,
        string $artistName,
    ): self {
        $artistProblematic = new self(
            artistId: $artistId,
            artistName: $artistName
        );
        $artistProblematic->status = 5;

        return $artistProblematic;
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

    public function getArtistId(): int
    {
        return $this->artistId;
    }

    public function setArtistId(int $artistId): void
    {
        $this->artistId = $artistId;
    }

    public function getArtistName(): string
    {
        return $this->artistName;
    }

    public function setArtistName(string $artistName): void
    {
        $this->artistName = $artistName;
    }

    public function getTidalUrl(): ?string
    {
        return $this->tidalUrl;
    }

    public function setTidalUrl(?string $tidalUrl): void
    {
        $this->tidalUrl = $tidalUrl;
    }

    public function getSpotifyUrl(): ?string
    {
        return $this->spotifyUrl;
    }

    public function setSpotifyUrl(?string $spotifyUrl): void
    {
        $this->spotifyUrl = $spotifyUrl;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function updateStatus(int $status): void
    {
        $this->status = $status;
    }
}
