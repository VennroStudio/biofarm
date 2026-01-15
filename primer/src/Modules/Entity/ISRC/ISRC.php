<?php

declare(strict_types=1);

namespace App\Modules\Entity\ISRC;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: ISRC::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_ISRC_ID', columns: ['isrc_id'])]
final class ISRC
{
    public const DB_NAME = 'isrc';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $isrcId;

    #[ORM\Column(type: 'integer')]
    private int $duration;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $recordingVersion;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $recordingType;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $recordingYear;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $recordingArtistName;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isExplicit = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $releaseLabel;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $icpn;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $releaseDate;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $genre;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $releaseName;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $releaseArtistName;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $recordingTitle;

    private function __construct(
        string $isrcId,
        int $duration,
        ?string $recordingVersion,
        ?string $recordingType,
        ?int $recordingYear,
        ?string $recordingArtistName,
        bool $isExplicit,
        ?string $releaseLabel,
        ?string $icpn,
        ?int $releaseDate,
        array $genre,
        ?string $releaseName,
        ?string $releaseArtistName,
        ?string $recordingTitle
    ) {
        $this->isrcId = $isrcId;
        $this->duration = $duration;
        $this->recordingVersion = $recordingVersion;
        $this->recordingType = $recordingType;
        $this->recordingYear = $recordingYear;
        $this->recordingArtistName = $recordingArtistName;
        $this->isExplicit = $isExplicit;
        $this->releaseLabel = $releaseLabel;
        $this->icpn = $icpn;
        $this->releaseDate = $releaseDate;
        $this->genre = json_encode($genre);
        $this->releaseName = $releaseName;
        $this->releaseArtistName = $releaseArtistName;
        $this->recordingTitle = $recordingTitle;
    }

    public static function create(
        string $isrcId,
        int $duration,
        ?string $recordingVersion,
        ?string $recordingType,
        ?int $recordingYear,
        ?string $recordingArtistName,
        bool $isExplicit,
        ?string $releaseLabel,
        ?string $icpn,
        ?int $releaseDate,
        array $genre,
        ?string $releaseName,
        ?string $releaseArtistName,
        ?string $recordingTitle
    ): self {
        return new self(
            isrcId: $isrcId,
            duration: $duration,
            recordingVersion: $recordingVersion,
            recordingType: $recordingType,
            recordingYear: $recordingYear,
            recordingArtistName: $recordingArtistName,
            isExplicit: $isExplicit,
            releaseLabel: $releaseLabel,
            icpn: $icpn,
            releaseDate: $releaseDate,
            genre: $genre,
            releaseName: $releaseName,
            releaseArtistName: $releaseArtistName,
            recordingTitle: $recordingTitle
        );
    }

    public function edit(
        int $duration,
        ?string $recordingVersion,
        ?string $recordingType,
        ?int $recordingYear,
        ?string $recordingArtistName,
        ?string $releaseLabel,
        ?string $icpn,
        ?int $releaseDate,
        array $genre,
        ?string $releaseName,
        ?string $releaseArtistName,
        ?string $recordingTitle
    ): void {
        $this->duration = $duration;
        $this->recordingVersion = $recordingVersion;
        $this->recordingType = $recordingType;
        $this->recordingYear = $recordingYear;
        $this->recordingArtistName = $recordingArtistName;
        $this->releaseLabel = $releaseLabel;
        $this->icpn = $icpn;
        $this->releaseDate = $releaseDate;
        $this->genre = json_encode($genre);
        $this->releaseName = $releaseName;
        $this->releaseArtistName = $releaseArtistName;
        $this->recordingTitle = $recordingTitle;
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

    public function getIsrcId(): string
    {
        return $this->isrcId;
    }

    public function setIsrcId(string $isrcId): void
    {
        $this->isrcId = $isrcId;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function getRecordingVersion(): ?string
    {
        return $this->recordingVersion;
    }

    public function setRecordingVersion(?string $recordingVersion): void
    {
        $this->recordingVersion = $recordingVersion;
    }

    public function getRecordingType(): ?string
    {
        return $this->recordingType;
    }

    public function setRecordingType(?string $recordingType): void
    {
        $this->recordingType = $recordingType;
    }

    public function getRecordingYear(): ?int
    {
        return $this->recordingYear;
    }

    public function setRecordingYear(?int $recordingYear): void
    {
        $this->recordingYear = $recordingYear;
    }

    public function getRecordingArtistName(): ?string
    {
        return $this->recordingArtistName;
    }

    public function setRecordingArtistName(?string $recordingArtistName): void
    {
        $this->recordingArtistName = $recordingArtistName;
    }

    public function getIsExplicit(): ?bool
    {
        return $this->isExplicit;
    }

    public function setIsExplicit(?bool $isExplicit): void
    {
        $this->isExplicit = $isExplicit;
    }

    public function getReleaseLabel(): ?string
    {
        return $this->releaseLabel;
    }

    public function setReleaseLabel(?string $releaseLabel): void
    {
        $this->releaseLabel = $releaseLabel;
    }

    public function getIcpn(): ?string
    {
        return $this->icpn;
    }

    public function setIcpn(?string $icpn): void
    {
        $this->icpn = $icpn;
    }

    public function getReleaseDate(): ?int
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?int $releaseDate): void
    {
        $this->releaseDate = $releaseDate;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): void
    {
        $this->genre = $genre;
    }

    public function getReleaseName(): ?string
    {
        return $this->releaseName;
    }

    public function setReleaseName(?string $releaseName): void
    {
        $this->releaseName = $releaseName;
    }

    public function getReleaseArtistName(): ?string
    {
        return $this->releaseArtistName;
    }

    public function setReleaseArtistName(?string $releaseArtistName): void
    {
        $this->releaseArtistName = $releaseArtistName;
    }

    public function getRecordingTitle(): ?string
    {
        return $this->recordingTitle;
    }

    public function setRecordingTitle(?string $recordingTitle): void
    {
        $this->recordingTitle = $recordingTitle;
    }
}
