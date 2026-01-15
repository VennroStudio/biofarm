<?php

declare(strict_types=1);

namespace App\Components\AppleGrab\Entities;

readonly class Album
{
    public function __construct(
        public string $id,
        public ?string $upc,
        public string $name,
        public bool $isCompilation,
        public bool $isSingle,
        public ?int $releaseAt,
        public int $totalTracks,
        public string $artistsString,
        public ?string $imageCover,
        public ?string $videoCover,
        public ?string $copyright,
        public ?string $label,
        public ?array $genreNames,
        public array $attributes,
        /** @var Artist[] */
        public array $artists,
    ) {}
}
