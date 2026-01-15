<?php

declare(strict_types=1);

namespace App\Components\AppleGrab\Entities;

readonly class Track
{
    public function __construct(
        public string $id,
        public int $discNumber,
        public int $trackNumber,
        public ?string $isrc,
        public string $name,
        public string $artistsString,
        public ?string $composers,
        public int $duration,
        public ?array $genreNames,
        public ?array $attributes,
        /** @var Artist[] */
        public array $artists,
    ) {}
}
