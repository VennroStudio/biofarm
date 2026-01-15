<?php

declare(strict_types=1);

namespace App\Modules\Entity\TidalTrack;

use App\Components\Helper;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;

#[ORM\Entity]
#[ORM\Table(name: TidalTrack::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_TIDAL_TRACK', columns: ['tidal_album_id', 'tidal_id'])]
#[ORM\Index(fields: ['tidalId'], name: 'IDX_TIDAL_ID')]
final class TidalTrack
{
    public const DB_NAME = 'tidal_tracks';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $tidalAlbumId;

    #[ORM\Column(type: 'bigint')]
    private int|string $tidalId;

    #[ORM\Column(type: 'integer')]
    private int $diskNumber;

    #[ORM\Column(type: 'integer')]
    private int $trackNumber;

    #[ORM\Column(type: 'string', length: 20)]
    private string $isrc;

    #[ORM\Column(type: 'string', length: 500)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $artists;

    #[ORM\Column(type: 'boolean')]
    private bool $explicit;

    #[ORM\Column(type: 'integer')]
    private int $duration;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $version;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $copyright;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $mediaMetadata;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $properties;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 2)]
    private float|string $popularity;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $attributes = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted;

    private function __construct(
        int $tidalAlbumId,
        string $tidalId,
        int $diskNumber,
        int $trackNumber,
        string $isrc,
        string $name,
        array $artists,
        bool $explicit,
        int $duration,
        ?string $version,
        ?string $copyright,
        ?array $mediaMetadata,
        ?array $properties,
        float $popularity,
    ) {
        $this->tidalAlbumId = $tidalAlbumId;
        $this->tidalId = $tidalId;
        $this->diskNumber = $diskNumber;
        $this->trackNumber = $trackNumber;
        $this->isrc = $isrc;
        $this->name = $name;
        $this->artists = json_encode($artists);
        $this->explicit = $explicit;
        $this->duration = $duration;
        $this->version = $version;
        $this->copyright = $copyright;
        $this->mediaMetadata = null !== $mediaMetadata ? json_encode($mediaMetadata) : null;
        $this->properties = null !== $properties ? json_encode($properties) : null;
        $this->popularity = $popularity;
        $this->isDeleted = false;
    }

    /** @throws Exception */
    public static function create(
        int $tidalAlbumId,
        string $tidalId,
        int $diskNumber,
        int $trackNumber,
        string $isrc,
        string $name,
        array $artists,
        bool $explicit,
        int $duration,
        ?string $version,
        ?string $copyright,
        ?array $mediaMetadata,
        ?array $properties,
        float $popularity,
    ): self {
        $name = Helper::textFormatter($name);
        if ($name === '') {
            throw new Exception('Empty track name! TidalId: ' . $tidalId);
        }

        $isrc = trim($isrc);
        if ($isrc === '') {
            throw new Exception('Empty track ISRC!');
        }

        return new self(
            tidalAlbumId: $tidalAlbumId,
            tidalId: $tidalId,
            diskNumber: $diskNumber,
            trackNumber: $trackNumber,
            isrc: $isrc,
            name: $name,
            artists: $artists,
            explicit: $explicit,
            duration: $duration,
            version: $version,
            copyright: $copyright,
            mediaMetadata: $mediaMetadata,
            properties: $properties,
            popularity: $popularity
        );
    }

    /** @throws Exception */
    public function edit(
        int $diskNumber,
        int $trackNumber,
        string $isrc,
        string $name,
        array $artists,
        bool $explicit,
        int $duration,
        ?string $version,
        ?string $copyright,
        ?array $mediaMetadata,
        ?array $properties,
        float $popularity,
        array $attributes,
    ): void {
        $name = Helper::textFormatter($name);
        if ($name === '') {
            throw new Exception('Empty track name! TidalId: ' . $this->tidalId);
        }

        $isrc = trim($isrc);
        if ($isrc === '') {
            throw new Exception('Empty track ISRC!');
        }

        $this->diskNumber = $diskNumber;
        $this->trackNumber = $trackNumber;
        $this->isrc = $isrc;
        $this->name = $name;
        $this->explicit = $explicit;
        $this->duration = $duration;
        $this->artists = json_encode($artists);
        $this->version = null !== $version ? Helper::textFormatter($version) : null;
        $this->copyright = $copyright;
        $this->mediaMetadata = null !== $mediaMetadata ? json_encode($mediaMetadata) : null;
        $this->properties = null !== $properties ? json_encode($properties) : null;
        $this->popularity = $popularity;
        $this->attributes = json_encode($attributes);
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

    public function getTidalAlbumId(): int
    {
        return $this->tidalAlbumId;
    }

    public function setTidalAlbumId(int $tidalAlbumId): void
    {
        $this->tidalAlbumId = $tidalAlbumId;
    }

    public function getTidalId(): int
    {
        return (int)$this->tidalId;
    }

    public function setTidalId(int $tidalId): void
    {
        $this->tidalId = $tidalId;
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

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }

    public function getCopyright(): ?string
    {
        return $this->copyright;
    }

    public function setCopyright(?string $copyright): void
    {
        $this->copyright = $copyright;
    }

    public function getMediaMetadata(): ?string
    {
        return $this->mediaMetadata;
    }

    public function setMediaMetadata(?string $mediaMetadata): void
    {
        $this->mediaMetadata = $mediaMetadata;
    }

    public function isDolbyAtmos(): bool
    {
        if (null === $this->mediaMetadata) {
            return false;
        }

        /** @var array{tags: string[]} $data */
        $data = json_decode($this->mediaMetadata, true);

        return \in_array('DOLBY_ATMOS', $data['tags'], true);
    }

    public function getProperties(): ?string
    {
        return $this->properties;
    }

    public function setProperties(?string $properties): void
    {
        $this->properties = $properties;
    }

    public function getIsrc(): string
    {
        return $this->isrc;
    }

    /** @throws Exception */
    public function setIsrc(string $isrc): void
    {
        $isrc = trim($isrc);
        if ($isrc === '') {
            throw new Exception('Empty track ISRC!');
        }

        $this->isrc = $isrc;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function getPopularity(): float
    {
        return (float)$this->popularity;
    }

    public function setPopularity(float $popularity): void
    {
        $this->popularity = $popularity;
    }

    public function isExplicit(): bool
    {
        return $this->explicit;
    }

    public function setExplicit(bool $explicit): void
    {
        $this->explicit = $explicit;
    }

    public function getArtists(): string
    {
        return $this->artists;
    }

    public function setArtists(string $artists): void
    {
        $this->artists = $artists;
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
