<?php

declare(strict_types=1);

namespace App\Components\ISRCSearch;

class ISRCInfo
{
    public function __construct(
        public string $id,
        public int $duration,
        public ?string $recordingVersion,
        public ?string $recordingType,
        public ?int $recordingYear,
        public ?string $recordingArtistName,
        public bool $isExplicit,
        public ?string $releaseLabel,
        public ?string $icpn,
        public ?int $releaseDate,
        public array $genre,
        public ?string $releaseName,
        public ?string $releaseArtistName,
        public ?string $recordingTitle,
    ) {}
}
