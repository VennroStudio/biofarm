<?php

declare(strict_types=1);

namespace App\Components\TidalGrab\Entities;

readonly class Track
{
    public function __construct(
        public string $id,
        public int $discNumber,
        public int $trackNumber,
        public string $isrc,
        public string $name,
        public array $artists,
        public bool $explicit,
        public int $duration,
        public ?string $version,
        public ?string $copyright,
        public ?array $mediaMetadata,
        public ?array $properties,
        public float $popularity,
    ) {}
}
