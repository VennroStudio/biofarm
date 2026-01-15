<?php

declare(strict_types=1);

namespace App\Modules\Entity\TrackProblematic;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: 'track_problematic')]
class TrackProblematic
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $loTrackId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $artistId = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $unionId;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $artistName;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $trackName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $tidalUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $spotifyUrl = null;

    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $status = 0;

    private function __construct(
        int $loTrackId,
        int $unionId,
        string $artistName,
        string $trackName,
    ) {
        $this->loTrackId = $loTrackId;
        $this->unionId = $unionId;
        $this->artistName = $artistName;
        $this->trackName = $trackName;
    }

    public static function create(
        int $loTrackId,
        int $unionId,
        string $artistName,
        string $trackName,
    ): self {
        return new self(
            loTrackId: $loTrackId,
            unionId: $unionId,
            artistName: $artistName,
            trackName: $trackName
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

    public function getLoTrackId(): int
    {
        return $this->loTrackId;
    }

    public function setLoTrackId(int $loTrackId): void
    {
        $this->loTrackId = $loTrackId;
    }

    public function getArtistId(): ?int
    {
        return $this->artistId;
    }

    public function setArtistId(?int $artistId): void
    {
        $this->artistId = $artistId;
    }

    public function getUnionId(): int
    {
        return $this->unionId;
    }

    public function setUnionId(int $unionId): void
    {
        $this->unionId = $unionId;
    }

    public function getArtistName(): string
    {
        return $this->artistName;
    }

    public function setArtistName(string $artistName): void
    {
        $this->artistName = $artistName;
    }

    public function getTrackName(): string
    {
        return $this->trackName;
    }

    public function setTrackName(string $trackName): void
    {
        $this->trackName = $trackName;
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
