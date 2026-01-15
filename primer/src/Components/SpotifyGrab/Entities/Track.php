<?php

declare(strict_types=1);

namespace App\Components\SpotifyGrab\Entities;

readonly class Track
{
    public function __construct(
        public string $id,
        public ?string $linkedId,
        public string $type,
        public int $discNumber,
        public int $trackNumber,
        public string $name,
        public bool $explicit,
        public int $duration,
        public bool $isLocal,
        public array $availableMarkets,
        public array $artists,
        public ?string $isrc,
    ) {}
}
