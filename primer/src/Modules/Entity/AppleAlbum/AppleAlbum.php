<?php

declare(strict_types=1);

namespace App\Modules\Entity\AppleAlbum;

use App\Components\Helper;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;

#[ORM\Entity]
#[ORM\Table(name: AppleAlbum::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_APPLE_ALBUM', columns: ['artist_id', 'apple_id'])]
#[ORM\Index(fields: ['artistId'], name: 'IDX_ARTIST_ID')]
#[ORM\Index(fields: ['appleId'], name: 'IDX_APPLE_ID')]
final class AppleAlbum
{
    public const DB_NAME = 'apple_albums';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $artistId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $appleId;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $upc;

    #[ORM\Column(type: 'string', length: 500)]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    private bool $isCompilation;

    #[ORM\Column(type: 'boolean')]
    private bool $isSingle;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $releasedAt;

    #[ORM\Column(type: 'integer')]
    private int $totalTracks;

    #[ORM\Column(type: 'text')]
    private string $artists;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $images;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $videos;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $copyrights;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $label;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $genreNames;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $attributes;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $updatedAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted;

    /** @throws Exception */
    private function __construct(
        int $artistId,
        string $appleId,
        ?string $upc,
        string $name,
        bool $isCompilation,
        bool $isSingle,
        ?int $releasedAt,
        int $totalTracks,
        string $artists,
        ?string $image,
        ?string $video,
        ?string $copyrights,
        ?string $label,
        ?array $genreNames,
        ?array $attributes,
    ) {
        $name = Helper::textFormatter($name);
        if ($name === '') {
            throw new Exception('Empty album name!');
        }

        if (null !== $upc) {
            $upc = trim($upc);
            if ($upc === '') {
                throw new Exception('Empty album upc!');
            }
        }

        $this->artistId = $artistId;
        $this->appleId = $appleId;
        $this->upc = $upc;
        $this->name = $name;
        $this->isCompilation = $isCompilation;
        $this->isSingle = $isSingle;
        $this->releasedAt = $releasedAt;
        $this->totalTracks = $totalTracks;
        $this->artists = $artists;
        $this->images = $image;
        $this->videos = $video;
        $this->copyrights = $copyrights;
        $this->label = $label;
        $this->genreNames = null !== $genreNames ? json_encode($genreNames) : null;
        $this->attributes = null !== $attributes ? json_encode($attributes) : null;
        $this->updatedAt = 0;
        $this->isDeleted = false;
    }

    /** @throws Exception */
    public static function create(
        int $artistId,
        string $appleId,
        ?string $upc,
        string $name,
        bool $isCompilation,
        bool $isSingle,
        ?int $releasedAt,
        int $totalTracks,
        string $artists,
        ?string $image,
        ?string $video,
        ?string $copyrights,
        ?string $label,
        ?array $genreNames,
        ?array $attributes,
    ): self {
        return new self(
            artistId: $artistId,
            appleId: $appleId,
            upc: $upc,
            name: $name,
            isCompilation: $isCompilation,
            isSingle: $isSingle,
            releasedAt: $releasedAt,
            totalTracks: $totalTracks,
            artists: $artists,
            image: $image,
            video: $video,
            copyrights: $copyrights,
            label: $label,
            genreNames: $genreNames,
            attributes: $attributes,
        );
    }

    /** @throws Exception */
    public function edit(
        ?string $upc,
        string $name,
        bool $isCompilation,
        bool $isSingle,
        ?int $releasedAt,
        int $totalTracks,
        string $artists,
        ?string $image,
        ?string $video,
        ?string $copyrights,
        ?string $label,
        ?array $genreNames,
        ?array $attributes,
    ): void {
        $name = Helper::textFormatter($name);
        if ($name === '') {
            throw new Exception('Empty album name!');
        }

        if (null !== $upc) {
            $upc = trim($upc);
            if ($upc === '') {
                throw new Exception('Empty album upc!');
            }
        }

        $this->upc = $upc;
        $this->name = $name;
        $this->isCompilation = $isCompilation;
        $this->isSingle = $isSingle;
        $this->releasedAt = $releasedAt;
        $this->totalTracks = $totalTracks;
        $this->artists = $artists;
        $this->images = $image;
        $this->videos = $video;
        $this->copyrights = $copyrights;
        $this->label = $label;
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

    public function getArtistId(): int
    {
        return $this->artistId;
    }

    public function setArtistId(int $artistId): void
    {
        $this->artistId = $artistId;
    }

    public function getAppleId(): string
    {
        return $this->appleId;
    }

    public function setAppleId(string $appleId): void
    {
        $this->appleId = $appleId;
    }

    public function getUpc(): ?string
    {
        return $this->upc;
    }

    public function setUpc(string $upc): void
    {
        $this->upc = $upc;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isCompilation(): bool
    {
        return $this->isCompilation;
    }

    public function setIsCompilation(bool $isCompilation): void
    {
        $this->isCompilation = $isCompilation;
    }

    public function isSingle(): bool
    {
        return $this->isSingle;
    }

    public function setIsSingle(bool $isSingle): void
    {
        $this->isSingle = $isSingle;
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

    public function getCopyrights(): ?string
    {
        return $this->copyrights;
    }

    public function setCopyrights(?string $copyrights): void
    {
        $this->copyrights = $copyrights;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
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

    public function getAttributes(): ?array
    {
        if (null === $this->attributes) {
            return null;
        }

        return (array)json_decode($this->attributes, true);
    }

    public function setAttributes(?string $attributes): void
    {
        $this->attributes = $attributes;
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
