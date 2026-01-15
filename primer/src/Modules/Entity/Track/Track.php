<?php

declare(strict_types=1);

namespace App\Modules\Entity\Track;

use App\Components\Helper;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;

#[ORM\Entity]
#[ORM\Table(name: Track::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_TIDAL_TRACK', columns: ['album_id', 'tidal_track_id'])]
#[ORM\UniqueConstraint(name: 'UNIQUE_SPOTIFY_TRACK', columns: ['album_id', 'spotify_id'])]
#[ORM\Index(fields: ['loTrackId'], name: 'IDX_LO_ID')]
#[ORM\Index(fields: ['tidalTrackId'], name: 'IDX_TIDAL_ID')]
#[ORM\Index(fields: ['appleTrackId'], name: 'IDX_APPLE_ID')]
#[ORM\Index(fields: ['spotifyId'], name: 'IDX_SPOTIFY_ID')]
#[ORM\Index(fields: ['spotifyOriginalId'], name: 'IDX_SPOTIFY_ORIGINAL_ID')]
#[ORM\Index(fields: ['isApproved', 'isReissued', 'loTrackId'], name: 'IDX_COUNTER')]
#[ORM\Index(fields: ['isApproved', 'spotifyAdditionalCheckedAt', 'id'], name: 'IDX_ADDITIONAL')]
final class Track
{
    public const DB_NAME = 'tracks';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $albumId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $loTrackId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $uploadedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tidalTrackId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $appleTrackId = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isAppleFound = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isApproved;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $approvedAt = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $spotifyId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $spotifyOriginalId;

    #[ORM\Column(type: 'integer')]
    private int $spotifyDiskNumber;

    #[ORM\Column(type: 'integer')]
    private int $spotifyTrackNumber;

    #[ORM\Column(type: 'string', length: 500)]
    private string $spotifyName;

    #[ORM\Column(type: 'boolean')]
    private bool $spotifyExplicit;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyArtists = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $spotifyISRC = null;

    #[ORM\Column(type: 'integer')]
    private int $spotifyDuration;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $spotifyType = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $spotifyIsLocal = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyAvailableMarkets = null;

    #[ORM\Column(type: 'boolean')]
    private bool $spotifyIsDeleted;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $spotifyAdditionalCheckedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $lyricsUpdatedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lyrics = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lyricsNetease = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isReissued = false;

    private function __construct(
        int $albumId,
        string $spotifyId,
        ?string $spotifyOriginalId,
        int $spotifyDiskNumber,
        int $spotifyTrackNumber,
        string $spotifyName,
        bool $spotifyExplicit,
        int $spotifyDuration,
    ) {
        $this->isApproved = false;
        $this->albumId = $albumId;
        $this->spotifyId = $spotifyId;
        $this->spotifyOriginalId = $spotifyOriginalId;
        $this->spotifyDiskNumber = $spotifyDiskNumber;
        $this->spotifyTrackNumber = $spotifyTrackNumber;
        $this->spotifyName = $spotifyName;
        $this->spotifyExplicit = $spotifyExplicit;
        $this->spotifyDuration = $spotifyDuration;
        $this->spotifyIsDeleted = false;
    }

    /** @throws Exception */
    public static function createSpotify(
        int $albumId,
        string $spotifyId,
        ?string $spotifyOriginalId,
        int $diskNumber,
        int $trackNumber,
        string $name,
        bool $explicit,
        int $duration,
    ): self {
        $name = Helper::textFormatter($name);

        if ($name === '' && $duration > 0) {
            throw new Exception('Empty track name! SpotifyId: ' . $spotifyId);
        }

        return new self(
            albumId: $albumId,
            spotifyId: $spotifyId,
            spotifyOriginalId: $spotifyOriginalId,
            spotifyDiskNumber: $diskNumber,
            spotifyTrackNumber: $trackNumber,
            spotifyName: $name,
            spotifyExplicit: $explicit,
            spotifyDuration: $duration
        );
    }

    /** @throws Exception */
    public function editSpotifyInfo(
        ?string $spotifyOriginalId,
        int $diskNumber,
        int $trackNumber,
        string $name,
        bool $explicit,
        array $artists,
        ?string $isrc,
        int $duration,
        string $type,
        bool $isLocal,
        array $availableMarkets
    ): void {
        $name = Helper::textFormatter($name);

        if ($name === '' && $duration > 0) {
            throw new Exception('Empty track name! SpotifyId: ' . $this->spotifyId);
        }

        $this->spotifyOriginalId = $spotifyOriginalId;
        $this->spotifyDiskNumber = $diskNumber;
        $this->spotifyTrackNumber = $trackNumber;
        $this->spotifyName = $name;
        $this->spotifyExplicit = $explicit;
        $this->spotifyArtists = json_encode($artists);
        $this->spotifyISRC = $isrc !== '' && $isrc !== null ? $isrc : null;
        $this->spotifyDuration = $duration;
        $this->spotifyType = $type !== '' ? $type : null;
        $this->spotifyIsLocal = $isLocal;
        $this->spotifyAvailableMarkets = (!empty($availableMarkets)) ? json_encode($availableMarkets) : null;
        $this->spotifyIsDeleted = ($name === '' && $duration === 0);
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

    public function getLoTrackId(): ?int
    {
        return $this->loTrackId;
    }

    public function setLoTrackId(?int $loTrackId): void
    {
        $this->loTrackId = $loTrackId;
    }

    public function getUploadedAt(): ?int
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(?int $uploadedAt): void
    {
        $this->uploadedAt = $uploadedAt;
    }

    public function getTidalTrackId(): ?int
    {
        return $this->tidalTrackId;
    }

    public function setTidalTrackId(?int $tidalTrackId): void
    {
        $this->tidalTrackId = $tidalTrackId;
    }

    public function getAppleTrackId(): ?int
    {
        return $this->appleTrackId;
    }

    public function setAppleTrackId(?int $appleTrackId): void
    {
        $this->appleTrackId = $appleTrackId;
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

    public function getSpotifyId(): string
    {
        return $this->spotifyId;
    }

    public function setSpotifyId(string $spotifyId): void
    {
        $this->spotifyId = $spotifyId;
    }

    public function getSpotifyOriginalId(): ?string
    {
        return $this->spotifyOriginalId;
    }

    public function setSpotifyOriginalId(?string $spotifyOriginalId): void
    {
        $this->spotifyOriginalId = $spotifyOriginalId;
    }

    public function getSpotifyDiskNumber(): int
    {
        return $this->spotifyDiskNumber;
    }

    public function setSpotifyDiskNumber(int $spotifyDiskNumber): void
    {
        $this->spotifyDiskNumber = $spotifyDiskNumber;
    }

    public function getSpotifyTrackNumber(): int
    {
        return $this->spotifyTrackNumber;
    }

    public function setSpotifyTrackNumber(int $spotifyTrackNumber): void
    {
        $this->spotifyTrackNumber = $spotifyTrackNumber;
    }

    public function getSpotifyName(): string
    {
        return $this->spotifyName;
    }

    public function setSpotifyName(string $spotifyName): void
    {
        $this->spotifyName = Helper::textFormatter($spotifyName);
    }

    public function isSpotifyExplicit(): bool
    {
        return $this->spotifyExplicit;
    }

    public function setSpotifyExplicit(bool $spotifyExplicit): void
    {
        $this->spotifyExplicit = $spotifyExplicit;
    }

    public function getSpotifyArtists(): ?string
    {
        return $this->spotifyArtists;
    }

    public function setSpotifyArtists(?string $spotifyArtists): void
    {
        $this->spotifyArtists = $spotifyArtists;
    }

    public function getSeparateArtists(): string
    {
        /** @var array{name: string, type: string}[] $artistsJson */
        $artistsJson = json_decode($this->getSpotifyArtists() ?? '[]', true);
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

    public function getSpotifyISRC(): ?string
    {
        return (null !== $this->spotifyISRC) ? strtoupper($this->spotifyISRC) : null;
    }

    public function setSpotifyISRC(?string $spotifyISRC): void
    {
        $this->spotifyISRC = $spotifyISRC;
    }

    public function getSpotifyDuration(): int
    {
        return $this->spotifyDuration;
    }

    public function setSpotifyDuration(int $spotifyDuration): void
    {
        $this->spotifyDuration = $spotifyDuration;
    }

    public function getSpotifyType(): ?string
    {
        return $this->spotifyType;
    }

    public function setSpotifyType(?string $spotifyType): void
    {
        $this->spotifyType = $spotifyType;
    }

    public function getSpotifyIsLocal(): ?bool
    {
        return $this->spotifyIsLocal;
    }

    public function setSpotifyIsLocal(?bool $spotifyIsLocal): void
    {
        $this->spotifyIsLocal = $spotifyIsLocal;
    }

    public function getSpotifyAvailableMarkets(): ?string
    {
        return $this->spotifyAvailableMarkets;
    }

    public function setSpotifyAvailableMarkets(?string $spotifyAvailableMarkets): void
    {
        $this->spotifyAvailableMarkets = $spotifyAvailableMarkets;
    }

    public function isSpotifyIsDeleted(): bool
    {
        return $this->spotifyIsDeleted;
    }

    public function setSpotifyIsDeleted(bool $spotifyIsDeleted): void
    {
        $this->spotifyIsDeleted = $spotifyIsDeleted;
    }

    public function getSpotifyAdditionalCheckedAt(): ?int
    {
        return $this->spotifyAdditionalCheckedAt;
    }

    public function setSpotifyAdditionalChecked(): void
    {
        $this->spotifyAdditionalCheckedAt = time();
    }

    public function getLyricsUpdatedAt(): ?int
    {
        return $this->lyricsUpdatedAt;
    }

    public function setLyricsUpdatedAt(?int $lyricsUpdatedAt): void
    {
        $this->lyricsUpdatedAt = $lyricsUpdatedAt;
    }

    public function getLyrics(): ?string
    {
        return $this->lyrics;
    }

    public function setLyrics(?string $lyrics): void
    {
        $this->lyrics = $lyrics;
    }

    public function getLyricsNetease(): ?string
    {
        return $this->lyricsNetease;
    }

    public function setLyricsNetease(?string $lyricsNetease): void
    {
        $this->lyricsNetease = $lyricsNetease;
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
