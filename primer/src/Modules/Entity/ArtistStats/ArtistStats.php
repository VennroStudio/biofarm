<?php

declare(strict_types=1);

namespace App\Modules\Entity\ArtistStats;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: ArtistStats::DB_NAME)]
final class ArtistStats
{
    public const DB_NAME = 'artist_stats';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $artistId;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $spotifyCountSocials = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $tidalCountSocials = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $appleCountSocials = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $spotifyCountAlbums = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $tidalCountAlbums = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $appleCountAlbums = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $countApproved = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $countApprovedWithTracks = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $countConflicts = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $countLoaded = 0;

    private function __construct(
        int $artistId,
    ) {
        $this->artistId = $artistId;
    }

    public static function create(
        int $artistId,
    ): self {
        return new self(
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

    public function getArtistId(): int
    {
        return $this->artistId;
    }

    public function setArtistId(int $artistId): void
    {
        $this->artistId = $artistId;
    }

    public function getSpotifyCountSocials(): int
    {
        return $this->spotifyCountSocials;
    }

    public function setSpotifyCountSocials(int $spotifyCountSocials): void
    {
        $this->spotifyCountSocials = $spotifyCountSocials;
    }

    public function getTidalCountSocials(): int
    {
        return $this->tidalCountSocials;
    }

    public function setTidalCountSocials(int $tidalCountSocials): void
    {
        $this->tidalCountSocials = $tidalCountSocials;
    }

    public function getAppleCountSocials(): int
    {
        return $this->appleCountSocials;
    }

    public function setAppleCountSocials(int $appleCountSocials): void
    {
        $this->appleCountSocials = $appleCountSocials;
    }

    public function getSpotifyCountAlbums(): int
    {
        return $this->spotifyCountAlbums;
    }

    public function setSpotifyCountAlbums(int $spotifyCountAlbums): void
    {
        $this->spotifyCountAlbums = $spotifyCountAlbums;
    }

    public function getTidalCountAlbums(): int
    {
        return $this->tidalCountAlbums;
    }

    public function setTidalCountAlbums(int $tidalCountAlbums): void
    {
        $this->tidalCountAlbums = $tidalCountAlbums;
    }

    public function getAppleCountAlbums(): int
    {
        return $this->appleCountAlbums;
    }

    public function setAppleCountAlbums(int $appleCountAlbums): void
    {
        $this->appleCountAlbums = $appleCountAlbums;
    }

    public function getCountApproved(): int
    {
        return $this->countApproved;
    }

    public function setCountApproved(int $countApproved): void
    {
        $this->countApproved = $countApproved;
    }

    public function getCountApprovedWithTracks(): int
    {
        return $this->countApprovedWithTracks;
    }

    public function setCountApprovedWithTracks(int $countApprovedWithTracks): void
    {
        $this->countApprovedWithTracks = $countApprovedWithTracks;
    }

    public function getCountConflicts(): int
    {
        return $this->countConflicts;
    }

    public function setCountConflicts(int $countConflicts): void
    {
        $this->countConflicts = $countConflicts;
    }

    public function getCountLoaded(): int
    {
        return $this->countLoaded;
    }

    public function setCountLoaded(int $countLoaded): void
    {
        $this->countLoaded = $countLoaded;
    }
}
