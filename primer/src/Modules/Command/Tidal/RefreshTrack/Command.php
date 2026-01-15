<?php

declare(strict_types=1);

namespace App\Modules\Command\Tidal\RefreshTrack;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        public ?int $albumId,
        #[Assert\NotBlank]
        public string $trackId,
        #[Assert\NotBlank]
        public int $diskNumber,
        #[Assert\NotBlank]
        public int $trackNumber,
        #[Assert\NotBlank]
        public string $isrc,
        #[Assert\NotBlank]
        public string $name,
        #[Assert\NotBlank]
        public array $artists,
        public bool $explicit,
        #[Assert\NotBlank]
        public int $duration,
        public ?string $version,
        public ?string $copyright,
        public ?array $mediaMetadata,
        public ?array $properties,
        public float $popularity,
        public array $attributes,
    ) {}
}
