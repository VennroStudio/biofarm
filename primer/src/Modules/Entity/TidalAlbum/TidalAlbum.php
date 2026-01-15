<?php

declare(strict_types=1);

namespace App\Modules\Entity\TidalAlbum;

use App\Components\Helper;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;

#[ORM\Entity]
#[ORM\Table(name: TidalAlbum::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_TIDAL_ALBUM', columns: ['artist_id', 'tidal_id'])]
#[ORM\Index(fields: ['artistId'], name: 'IDX_ARTIST_ID')]
#[ORM\Index(fields: ['tidalId'], name: 'IDX_TIDAL_ID')]
final class TidalAlbum
{
    public const DB_NAME = 'tidal_albums';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $artistId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $tidalId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 500)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $artists;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $images;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $videos;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $releasedAt;

    #[ORM\Column(type: 'integer')]
    private int $totalTracks;

    #[ORM\Column(type: 'string', length: 50)]
    private string $barcodeId;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $copyrights;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $mediaMetadata;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $properties;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $updatedAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted;

    /** @throws Exception */
    private function __construct(
        int $artistId,
        string $tidalId,
        string $type,
        string $barcodeId,
        string $name,
        array $artists,
        ?array $images,
        ?array $videos,
        ?int $releasedAt,
        int $totalTracks,
        ?string $copyrights,
        ?array $mediaMetadata,
        ?array $properties,
    ) {
        $type = trim($type);
        if ($type === '') {
            throw new Exception('Empty album type!');
        }

        $name = Helper::textFormatter($name);
        if ($name === '') {
            throw new Exception('Empty album name!');
        }

        $barcodeId = trim($barcodeId);
        if ($barcodeId === '') {
            throw new Exception('Empty album barcodeId!');
        }

        $this->artistId = $artistId;
        $this->tidalId = $tidalId;
        $this->type = $type;
        $this->barcodeId = $barcodeId;
        $this->name = $name;
        $this->artists = json_encode($artists);
        $this->images = null !== $images ? json_encode($images) : null;
        $this->videos = null !== $videos ? json_encode($videos) : null;
        $this->releasedAt = $releasedAt;
        $this->totalTracks = $totalTracks;
        $this->copyrights = $copyrights;
        $this->mediaMetadata = null !== $mediaMetadata ? json_encode($mediaMetadata) : null;
        $this->properties = null !== $properties ? json_encode($properties) : null;
        $this->updatedAt = 0;
        $this->isDeleted = false;
    }

    /** @throws Exception */
    public static function create(
        int $artistId,
        string $tidalId,
        string $type,
        string $barcodeId,
        string $name,
        array $artists,
        ?array $images,
        ?array $videos,
        ?int $releasedAt,
        int $totalTracks,
        ?string $copyrights,
        ?array $mediaMetadata,
        ?array $properties,
    ): self {
        return new self(
            artistId: $artistId,
            tidalId: $tidalId,
            type: $type,
            barcodeId: $barcodeId,
            name: Helper::textFormatter($name),
            artists: $artists,
            images: $images,
            videos: $videos,
            releasedAt: $releasedAt,
            totalTracks: $totalTracks,
            copyrights: $copyrights,
            mediaMetadata: $mediaMetadata,
            properties: $properties
        );
    }

    /** @throws Exception */
    public function edit(
        string $type,
        string $barcodeId,
        string $name,
        array $artists,
        ?array $images,
        ?array $videos,
        ?int $releasedAt,
        int $totalTracks,
        ?string $copyrights,
        ?array $mediaMetadata,
        ?array $properties,
    ): void {
        $type = trim($type);
        if ($type === '') {
            throw new Exception('Empty album type!');
        }

        $name = Helper::textFormatter($name);
        if ($name === '') {
            throw new Exception('Empty album name!');
        }

        $barcodeId = trim($barcodeId);
        if ($barcodeId === '') {
            throw new Exception('Empty album barcodeId!');
        }

        $this->type = $type;
        $this->barcodeId = $barcodeId;
        $this->name = $name;
        $this->artists = json_encode($artists);
        $this->images = null !== $images ? json_encode($images) : null;
        $this->videos = null !== $videos ? json_encode($videos) : null;
        $this->releasedAt = $releasedAt;
        $this->totalTracks = $totalTracks;
        $this->copyrights = $copyrights;
        $this->mediaMetadata = null !== $mediaMetadata ? json_encode($mediaMetadata) : null;
        $this->properties = null !== $properties ? json_encode($properties) : null;
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

    public function getTidalId(): string
    {
        return $this->tidalId;
    }

    public function setTidalId(string $tidalId): void
    {
        $this->tidalId = $tidalId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getBarcodeId(): string
    {
        return $this->barcodeId;
    }

    public function setBarcodeId(string $barcodeId): void
    {
        $this->barcodeId = $barcodeId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = Helper::textFormatter($name);
    }

    public function getArtists(): string
    {
        return $this->artists;
    }

    public function setArtists(string $artists): void
    {
        $this->artists = $artists;
    }

    public function getImages(): ?string
    {
        return $this->images;
    }

    public function setImages(?string $images): void
    {
        $this->images = $images;
    }

    public function getVideos(): ?string
    {
        return $this->videos;
    }

    public function setVideos(?string $videos): void
    {
        $this->videos = $videos;
    }

    public function getReleasedAt(): ?int
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(?int $releasedAt): void
    {
        $this->releasedAt = $releasedAt;
    }

    public function getTotalTracks(): int
    {
        return $this->totalTracks;
    }

    public function setTotalTracks(int $totalTracks): void
    {
        $this->totalTracks = $totalTracks;
    }

    public function getMediaMetadata(): ?string
    {
        return $this->mediaMetadata;
    }

    public function setMediaMetadata(?string $mediaMetadata): void
    {
        $this->mediaMetadata = $mediaMetadata;
    }

    public function getProperties(): ?string
    {
        return $this->properties;
    }

    public function setProperties(?string $properties): void
    {
        $this->properties = $properties;
    }

    public function isExplicit(): bool
    {
        if (null === $this->properties) {
            return false;
        }

        /** @var array{content: string[]|null} $properties */
        $properties = json_decode($this->properties, true);

        return \in_array('explicit', $properties['content'] ?? [], true);
    }

    public function getCopyrights(): ?string
    {
        return $this->copyrights;
    }

    public function setCopyrights(?string $copyrights): void
    {
        $this->copyrights = $copyrights;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(int $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
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
