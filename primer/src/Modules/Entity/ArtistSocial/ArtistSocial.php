<?php

declare(strict_types=1);

namespace App\Modules\Entity\ArtistSocial;

use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;

#[ORM\Entity]
#[ORM\Table(name: ArtistSocial::DB_NAME)]
#[ORM\Index(fields: ['artistId', 'type'], name: 'IDX_ARTIST_ID')]
final class ArtistSocial
{
    public const DB_NAME = 'artist_socials';

    public const TYPE_TIDAL = 0;
    public const TYPE_SPOTIFY = 1;
    public const TYPE_APPLE = 2;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $artistId;

    #[ORM\Column(type: 'integer')]
    private int $type;

    #[ORM\Column(type: 'string', length: 500)]
    private string $url;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $info = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rateCheckedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $similarIds = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $popularTrackIds = null;

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $deletedAt = null;

    /** @throws Exception */
    private function __construct(
        int $artistId,
        string $url,
        ?string $description,
    ) {
        $this->artistId = $artistId;
        $this->type = $this->getTypeByUrl($url);
        $this->url = $url;
        $this->description = $description;
        $this->createdAt = time();
    }

    public static function create(
        int $artistId,
        string $url,
        ?string $description,
    ): self {
        return new self(
            artistId: $artistId,
            url: $url,
            description: $description,
        );
    }

    /** @throws Exception */
    public function edit(
        string $url,
        ?string $description,
    ): void {
        $this->type = $this->getTypeByUrl($url);
        $this->url = $url;
        $this->description = $description;
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

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getIdByUrl(): string
    {
        $arr = explode('/', $this->url);
        return end($arr);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(?string $info): void
    {
        $this->info = $info;
    }

    public function getRateCheckedAt(): ?int
    {
        return $this->rateCheckedAt;
    }

    public function setRateChecked(): void
    {
        $this->rateCheckedAt = time();
    }

    public function getSimilarIds(): ?string
    {
        return $this->similarIds;
    }

    public function setSimilarIds(?string $similarIds): void
    {
        $this->similarIds = $similarIds;
    }

    public function getPopularTrackIds(): ?string
    {
        return $this->popularTrackIds;
    }

    public function setPopularTrackIds(?string $popularTrackIds): void
    {
        $this->popularTrackIds = $popularTrackIds;
    }

    public function getAppleGenres(): array
    {
        $infoJson = $this->getInfo();

        if (null === $infoJson) {
            return [];
        }

        /** @var array{attributes: array{genreNames: string[]}} $info */
        $info = (array)json_decode($infoJson, true);
        return $info['attributes']['genreNames'] ?? [];
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?int $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getDeletedAt(): ?int
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?int $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    /** @throws Exception */
    private function getTypeByUrl(string $url): int
    {
        if (str_contains($url, 'https://listen.tidal.com/artist/')) {
            return self::TYPE_TIDAL;
        }

        if (str_contains($url, 'https://open.spotify.com/artist/')) {
            return self::TYPE_SPOTIFY;
        }

        if (str_contains($url, 'https://music.apple.com/')) {
            return self::TYPE_APPLE;
        }

        throw new Exception('Invalid url');
    }
}
