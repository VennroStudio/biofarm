<?php

declare(strict_types=1);

namespace App\Modules\Command\ISRC\Refresh;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $id,
        #[Assert\NotBlank]
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
        public ?string $recordingTitle
    ) {}
}
