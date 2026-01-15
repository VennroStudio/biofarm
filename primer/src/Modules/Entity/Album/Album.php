<?php

declare(strict_types=1);

namespace App\Modules\Entity\Album;

use App\Components\Helper;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;

#[ORM\Entity]
#[ORM\Table(name: Album::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_TIDAL_ALBUM', columns: ['tidal_album_id'])]
#[ORM\UniqueConstraint(name: 'UNIQUE_APPLE_ALBUM', columns: ['apple_album_id'])]
#[ORM\UniqueConstraint(name: 'UNIQUE_SPOTIFY_ALBUM', columns: ['spotify_id'])]
#[ORM\Index(fields: ['allTracksMapped', 'isApproved', 'isReissued', 'loAlbumId'], name: 'IDX_COUNTER')]
#[ORM\Index(fields: ['spotifyType'], name: 'IDX_TYPE')]
final class Album
{
    public const DB_NAME = 'albums';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $loAlbumId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tidalAlbumId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $appleAlbumId = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isAppleFound = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isApproved;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $approvedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $allTracksMapped = false;

    #[ORM\Column(type: 'string', length: 255)]
    private string $spotifyId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $spotifyType;

    #[ORM\Column(type: 'string', length: 255)]
    private string $spotifyName;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $spotifyReleasedAt = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $spotifyReleasedPrecision = null;

    #[ORM\Column(type: 'integer')]
    private int $spotifyTotalTracks;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyAvailableMarkets = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyArtists = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $spotifyUPC = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyImages = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyCopyrights = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyExternalIds = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyGenres = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $spotifyLabel = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $spotifyPopularity = null;

    #[ORM\Column(type: 'boolean')]
    private bool $spotifyIsDeleted;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mergedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $similarCheckedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $similarTidalIds = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $similarSpotifyIds = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $updatedAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isReissued = false;

    private function __construct(
        string $spotifyId,
        string $spotifyType,
        string $spotifyName,
        int $spotifyTotalTracks,
        ?string $spotifyUPC,
    ) {
        $this->isApproved = false;
        $this->spotifyId = $spotifyId;
        $this->spotifyType = $spotifyType;
        $this->spotifyName = $spotifyName;
        $this->spotifyTotalTracks = $spotifyTotalTracks;
        $this->spotifyUPC = $spotifyUPC;
        $this->updatedAt = 0;
        $this->spotifyIsDeleted = false;
    }

    /** @throws Exception */
    public static function createSpotify(
        string $id,
        string $type,
        string $name,
        int $totalTracks,
        ?string $upc,
    ): self {
        $name = Helper::textFormatter($name);

        if ($name === '' && null !== $upc) {
            throw new Exception('Empty album name!');
        }

        return new self(
            spotifyId: $id,
            spotifyType: $type,
            spotifyName: $name,
            spotifyTotalTracks: $totalTracks,
            spotifyUPC: $upc
        );
    }

    /** @throws Exception */
    public function editSpotifyInfo(
        string $type,
        string $name,
        int $releasedAt,
        string $releasedPrecision,
        int $totalTracks,
        array $availableMarkets,
        array $artists,
        ?string $upc,
        array $images,
        array $copyrights,
        array $externalIds,
        array $genres,
        ?string $label,
        int $popularity,
    ): void {
        $name = Helper::textFormatter($name);

        if ($name === '' && null !== $upc) {
            throw new Exception('Empty album name!');
        }

        $this->spotifyType = $type;
        $this->spotifyName = $name;
        $this->spotifyReleasedAt = $releasedAt;
        $this->spotifyReleasedPrecision = $releasedPrecision;
        $this->spotifyTotalTracks = $totalTracks;
        $this->spotifyAvailableMarkets = (!empty($availableMarkets)) ? json_encode($availableMarkets) : null;
        $this->spotifyArtists = json_encode($artists);
        $this->spotifyUPC = $upc;
        $this->spotifyImages = (!empty($images)) ? json_encode($images) : null;
        $this->spotifyCopyrights = (!empty($copyrights)) ? json_encode($copyrights) : null;
        $this->spotifyExternalIds = (!empty($externalIds)) ? json_encode($externalIds) : null;
        $this->spotifyGenres = (!empty($genres)) ? json_encode($genres) : null;
        $this->spotifyLabel = $label;
        $this->spotifyPopularity = $popularity;
        $this->spotifyIsDeleted = ($name === '' && $upc === null);

        $this->updatedAt = time();
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

    public function getLoAlbumId(): ?int
    {
        return $this->loAlbumId;
    }

    public function setLoAlbumId(?int $loAlbumId): void
    {
        $this->loAlbumId = $loAlbumId;
    }

    public function getTidalAlbumId(): ?int
    {
        return $this->tidalAlbumId;
    }

    public function setTidalAlbumId(?int $tidalAlbumId): void
    {
        $this->tidalAlbumId = $tidalAlbumId;
    }

    public function getAppleAlbumId(): ?int
    {
        return $this->appleAlbumId;
    }

    public function setAppleAlbumId(?int $appleAlbumId): void
    {
        $this->appleAlbumId = $appleAlbumId;
    }

    public function isAppleFound(): bool
    {
        return $this->isAppleFound;
    }

    public function setAppleFound(): void
    {
        $this->isAppleFound = true;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function setApproved(): void
    {
        $this->isApproved = true;
    }

    public function setNotApproved(): void
    {
        $this->isApproved = false;
    }

    public function getApprovedAt(): ?int
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?int $approvedAt): void
    {
        $this->approvedAt = $approvedAt;
    }

    public function isAllTracksMapped(): bool
    {
        return $this->allTracksMapped;
    }

    public function setAllTracksMapped(bool $allTracksMapped): void
    {
        $this->allTracksMapped = $allTracksMapped;
    }

    public function getSpotifyId(): ?string
    {
        return $this->spotifyId;
    }

    public function getSpotifyType(): string
    {
        return $this->spotifyType;
    }

    public function setSpotifyType(string $spotifyType): void
    {
        $this->spotifyType = $spotifyType;
    }

    public function getSpotifyName(): string
    {
        return $this->spotifyName;
    }

    public function setSpotifyName(string $spotifyName): void
    {
        $this->spotifyName = Helper::textFormatter($spotifyName);
    }

    public function getSpotifyReleasedAt(): ?int
    {
        return $this->spotifyReleasedAt;
    }

    public function setSpotifyReleasedAt(?int $spotifyReleasedAt): void
    {
        $this->spotifyReleasedAt = $spotifyReleasedAt;
    }

    public function getSpotifyReleasedAtPrecision(): ?string
    {
        return $this->spotifyReleasedPrecision;
    }

    public function setSpotifyReleasedAtPrecision(?string $spotifyReleasedPrecision): void
    {
        $this->spotifyReleasedPrecision = $spotifyReleasedPrecision;
    }

    public function getSpotifyTotalTracks(): int
    {
        return $this->spotifyTotalTracks;
    }

    public function setSpotifyTotalTracks(int $spotifyTotalTracks): void
    {
        $this->spotifyTotalTracks = $spotifyTotalTracks;
    }

    public function getSpotifyAvailableMarkets(): ?string
    {
        return $this->spotifyAvailableMarkets;
    }

    public function setSpotifyAvailableMarkets(?string $spotifyAvailableMarkets): void
    {
        $this->spotifyAvailableMarkets = $spotifyAvailableMarkets;
    }

    public function getSpotifyArtists(): ?string
    {
        return $this->spotifyArtists;
    }

    public function setSpotifyArtists(?string $spotifyArtists): void
    {
        $this->spotifyArtists = $spotifyArtists;
    }

    public function getSeparateArtists(): ?string
    {
        if (null === $this->getSpotifyArtists()) {
            return null;
        }

        /** @var array{name: string, type: string}[] $artistsJson */
        $artistsJson = json_decode($this->getSpotifyArtists(), true);
        $artists = [];

        foreach ($artistsJson as $artist) {
            if ($artist['type'] === 'artist') {
                $artists[] = $artist['name'];
            }
        }

        return implode(', ', $artists);
    }

    /** @param string[] $spotifyArtistIds */
    public function getArtistNumber(array $spotifyArtistIds): ?int
    {
        foreach ($spotifyArtistIds as $spotifyArtistId) {
            $key = array_search($spotifyArtistId, $this->getArtistIds(), true);

            if ($key !== false) {
                return (int)$key + 1;
            }
        }

        return null;
    }

    /** @return string[] */
    public function getArtistIds(): array
    {
        if (null === $this->getSpotifyArtists()) {
            return [];
        }

        /** @var array{id: string, type: string}[] $artistsJson */
        $artistsJson = json_decode($this->getSpotifyArtists(), true);
        $artists = [];

        foreach ($artistsJson as $artist) {
            if ($artist['type'] === 'artist') {
                $artists[] = $artist['id'];
            }
        }

        return $artists;
    }

    public function getSpotifyUPC(): ?string
    {
        return $this->spotifyUPC;
    }

    public function setSpotifyUPC(?string $spotifyUPC): void
    {
        $this->spotifyUPC = $spotifyUPC;
    }

    public function getSpotifyImages(): ?string
    {
        return $this->spotifyImages;
    }

    public function setSpotifyImages(?string $spotifyImages): void
    {
        $this->spotifyImages = $spotifyImages;
    }

    public function getSpotifyCopyrights(): ?string
    {
        return $this->spotifyCopyrights;
    }

    public function setSpotifyCopyrights(?string $spotifyCopyrights): void
    {
        $this->spotifyCopyrights = $spotifyCopyrights;
    }

    public function getSpotifyExternalIds(): ?string
    {
        return $this->spotifyExternalIds;
    }

    public function setSpotifyExternalIds(?string $spotifyExternalIds): void
    {
        $this->spotifyExternalIds = $spotifyExternalIds;
    }

    public function getSpotifyUpcFromExternalIds(): ?string
    {
        if (null === $this->spotifyExternalIds) {
            return null;
        }

        /** @var array{upc: string} $externalJson */
        $externalJson = json_decode($this->spotifyExternalIds, true);

        return $externalJson['upc'] ?? null;
    }

    public function getSpotifyGenres(): ?string
    {
        return $this->spotifyGenres;
    }

    public function setSpotifyGenres(?string $spotifyGenres): void
    {
        $this->spotifyGenres = $spotifyGenres;
    }

    public function getSpotifyLabel(): ?string
    {
        return $this->spotifyLabel;
    }

    public function setSpotifyLabel(?string $spotifyLabel): void
    {
        $this->spotifyLabel = $spotifyLabel;
    }

    public function getSpotifyPopularity(): ?int
    {
        return $this->spotifyPopularity;
    }

    public function setSpotifyPopularity(?int $spotifyPopularity): void
    {
        $this->spotifyPopularity = $spotifyPopularity;
    }

    public function getMergedAt(): ?int
    {
        return $this->mergedAt;
    }

    public function setMergedAt(?int $mergedAt): void
    {
        $this->mergedAt = $mergedAt;
    }

    public function getSimilarCheckedAt(): ?int
    {
        return $this->similarCheckedAt;
    }

    public function setSimilarChecked(): void
    {
        $this->similarCheckedAt = time();
    }

    public function getSimilarTidalIds(): ?string
    {
        return $this->similarTidalIds;
    }

    public function setSimilarTidalIds(?string $similarTidalIds): void
    {
        $this->similarTidalIds = $similarTidalIds;
    }

    public function getSimilarSpotifyIds(): ?string
    {
        return $this->similarSpotifyIds;
    }

    public function setSimilarSpotifyIds(?string $similarSpotifyIds): void
    {
        $this->similarSpotifyIds = $similarSpotifyIds;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(int $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function isSpotifyIsDeleted(): bool
    {
        return $this->spotifyIsDeleted;
    }

    public function setSpotifyIsDeleted(bool $spotifyIsDeleted): void
    {
        $this->spotifyIsDeleted = $spotifyIsDeleted;
    }

    public function isReissued(): bool
    {
        return $this->isReissued;
    }

    public function setIsReissued(bool $isReissued): void
    {
        $this->isReissued = $isReissued;
    }
}
