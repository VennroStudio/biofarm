<?php

declare(strict_types=1);

namespace App\Modules\Entity\AppleTrack;

use App\Components\Helper;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;

#[ORM\Entity]
#[ORM\Table(name: AppleTrack::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_APPLE_TRACK', columns: ['apple_album_id', 'apple_id'])]
#[ORM\Index(fields: ['appleId'], name: 'IDX_APPLE_ID')]
final class AppleTrack
{
    public const DB_NAME = 'apple_tracks';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $appleAlbumId;

    #[ORM\Column(type: 'bigint')]
    private int|string $appleId;

    #[ORM\Column(type: 'integer')]
    private int $diskNumber;

    #[ORM\Column(type: 'integer')]
    private int $trackNumber;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $isrc;

    #[ORM\Column(type: 'string', length: 500)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $artists;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $composers;

    #[ORM\Column(type: 'integer')]
    private int $duration;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $genreNames;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $attributes;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted;

    private function __construct(
        int $appleAlbumId,
        string $appleId,
        int $diskNumber,
        int $trackNumber,
        ?string $isrc,
        string $name,
        string $artists,
        ?string $composers,
        int $duration,
        ?array $genreNames,
        ?array $attributes,
    ) {
        $this->appleAlbumId = $appleAlbumId;
        $this->appleId = $appleId;
        $this->diskNumber = $diskNumber;
        $this->trackNumber = $trackNumber;
        $this->isrc = $isrc;
        $this->name = $name;
        $this->artists = $artists;
        $this->composers = $composers;
        $this->duration = $duration;
        $this->genreNames = null !== $genreNames ? json_encode($genreNames) : null;
        $this->attributes = null !== $attributes ? json_encode($attributes) : null;
        $this->isDeleted = false;
    }

    /** @throws Exception */
    public static function create(
        int $appleAlbumId,
        string $appleId,
        int $diskNumber,
        int $trackNumber,
        ?string $isrc,
        string $name,
        string $artists,
        ?string $composers,
        int $duration,
        ?array $genreNames,
        ?array $attributes,
    ): self {
        $name = Helper::textFormatter($name);
        if ($name === '') {
            throw new Exception('Empty track name! AppleId: ' . $appleId);
        }

        if (null !== $isrc) {
            $isrc = trim($isrc);
            if ($isrc === '') {
                throw new Exception('Empty track ISRC!');
            }
        }

        return new self(
            appleAlbumId: $appleAlbumId,
            appleId: $appleId,
            diskNumber: $diskNumber,
            trackNumber: $trackNumber,
            isrc: $isrc,
            name: $name,
            artists: $artists,
            composers: $composers,
            duration: $duration,
            genreNames: $genreNames,
            attributes: $attributes,
        );
    }

    /** @throws Exception */
    public function edit(
        int $diskNumber,
        int $trackNumber,
        ?string $isrc,
        string $name,
        string $artists,
        ?string $composers,
        int $duration,
        ?array $genreNames,
        ?array $attributes,
    ): void {
        $name = Helper::textFormatter($name);
        if ($name === '') {
            throw new Exception('Empty track name! AppleId: ' . $this->appleId);
        }

        if (null !== $isrc) {
            $isrc = trim($isrc);
            if ($isrc === '') {
                throw new Exception('Empty track ISRC!');
            }
        }

        $this->diskNumber = $diskNumber;
        $this->trackNumber = $trackNumber;
        $this->isrc = $isrc;
        $this->name = $name;
        $this->artists = $artists;
        $this->composers = $composers;
        $this->duration = $duration;
        $this->genreNames = null !== $genreNames ? json_encode($genreNames) : null;
        $this->attributes = null !== $attributes ? json_encode($attributes) : null;
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

    public function getAppleAlbumId(): int
    {
        return $this->appleAlbumId;
    }

    public function setAppleAlbumId(int $appleAlbumId): void
    {
        $this->appleAlbumId = $appleAlbumId;
    }

    public function getAppleId(): int
    {
        return (int)$this->appleId;
    }

    public function setAppleId(int $appleId): void
    {
        $this->appleId = $appleId;
    }

    public function getDiskNumber(): int
    {
        return $this->diskNumber;
    }

    public function setDiskNumber(int $diskNumber): void
    {
        $this->diskNumber = $diskNumber;
    }

    public function getTrackNumber(): int
    {
        return $this->trackNumber;
    }

    public function setTrackNumber(int $trackNumber): void
    {
        $this->trackNumber = $trackNumber;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @throws Exception */
    public function setName(string $name): void
    {
        $name = Helper::textFormatter($name);
        if ($name === '') {
            throw new Exception('Empty track name!');
        }

        $this->name = $name;
    }

    public function getIsrc(): string
    {
        return $this->isrc ?? '';
    }

    public function setIsrc(string $isrc): void
    {
        $this->isrc = $isrc;
    }

    public function getArtists(): string
    {
        return $this->artists;
    }

    public function setArtists(string $artists): void
    {
        $this->artists = $artists;
    }

    public function getComposers(): ?string
    {
        return $this->composers;
    }

    public function setComposers(?string $composers): void
    {
        $this->composers = $composers;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    /** @return string[]|null */
    public function getGenreNames(): ?array
    {
        /** @var string[]|null */
        return null !== $this->genreNames ? json_decode($this->genreNames, true) : null;
    }

    public function setGenreNames(?string $genreNames): void
    {
        $this->genreNames = $genreNames;
    }

    public function getAttributes(): ?string
    {
        return $this->attributes;
    }

    public function setAttributes(?string $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setDeleted(): void
    {
        $this->isDeleted = true;
    }
}
