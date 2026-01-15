<?php

declare(strict_types=1);

namespace App\Modules\Entity\TrackAdditional;

use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;

#[ORM\Entity]
#[ORM\Table(name: TrackAdditional::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_TRACK', columns: ['track_id'])]
final class TrackAdditional
{
    public const DB_NAME = 'tracks_additional';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $trackId;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyFeatures = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyAnalysisMeta = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyAnalysisTrack = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyAnalysisBars = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyAnalysisBeats = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyAnalysisSections = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?string $spotifyAnalysisSegments = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $spotifyAnalysisTatums = null;

    private function __construct(
        int $trackId,
    ) {
        $this->trackId = $trackId;
    }

    /** @throws Exception */
    public static function create(
        int $trackId,
    ): self {
        return new self(
            trackId: $trackId,
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

    public function getTrackId(): int
    {
        return $this->trackId;
    }

    public function setTrackId(int $trackId): void
    {
        $this->trackId = $trackId;
    }

    public function getSpotifyFeatures(): ?string
    {
        return $this->spotifyFeatures;
    }

    public function setSpotifyFeatures(?string $spotifyFeatures): void
    {
        $this->spotifyFeatures = $spotifyFeatures;
    }

    public function getSpotifyAnalysisMeta(): ?string
    {
        return $this->spotifyAnalysisMeta;
    }

    public function setSpotifyAnalysisMeta(?string $spotifyAnalysisMeta): void
    {
        $this->spotifyAnalysisMeta = $spotifyAnalysisMeta;
    }

    public function getSpotifyAnalysisTrack(): ?string
    {
        return $this->spotifyAnalysisTrack;
    }

    public function setSpotifyAnalysisTrack(?string $spotifyAnalysisTrack): void
    {
        $this->spotifyAnalysisTrack = $spotifyAnalysisTrack;
    }

    public function getSpotifyAnalysisBars(): ?string
    {
        return $this->spotifyAnalysisBars;
    }

    public function setSpotifyAnalysisBars(?string $spotifyAnalysisBars): void
    {
        $this->spotifyAnalysisBars = $spotifyAnalysisBars;
    }

    public function getSpotifyAnalysisBeats(): ?string
    {
        return $this->spotifyAnalysisBeats;
    }

    public function setSpotifyAnalysisBeats(?string $spotifyAnalysisBeats): void
    {
        $this->spotifyAnalysisBeats = $spotifyAnalysisBeats;
    }

    public function getSpotifyAnalysisSections(): ?string
    {
        return $this->spotifyAnalysisSections;
    }

    public function setSpotifyAnalysisSections(?string $spotifyAnalysisSections): void
    {
        $this->spotifyAnalysisSections = $spotifyAnalysisSections;
    }

    public function getSpotifyAnalysisSegments(): ?string
    {
        return $this->spotifyAnalysisSegments;
    }

    public function setSpotifyAnalysisSegments(?string $spotifyAnalysisSegments): void
    {
        $this->spotifyAnalysisSegments = $spotifyAnalysisSegments;
    }

    public function getSpotifyAnalysisTatums(): ?string
    {
        return $this->spotifyAnalysisTatums;
    }

    public function setSpotifyAnalysisTatums(?string $spotifyAnalysisTatums): void
    {
        $this->spotifyAnalysisTatums = $spotifyAnalysisTatums;
    }
}
