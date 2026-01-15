<?php

declare(strict_types=1);

namespace App\Components\TidalGrab\Entities;

readonly class Album
{
    public function __construct(
        public string $id,
        public string $barcodeId,
        public string $title,
        public string $type,
        public ?int $release,
        public int $totalTracks,
        public array $artists,
        public ?array $imageCovers,
        public ?array $videoCovers,
        public ?string $copyright,
        public ?array $mediaMetadata,
        public ?array $properties,
    ) {}
}
