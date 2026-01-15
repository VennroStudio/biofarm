<?php

declare(strict_types=1);

namespace App\Components\SpotifyGrab\Entities;

use phpseclib3\File\ASN1\Maps\Name;

readonly class Album
{
    public function __construct(
        public string $id,
        public string $type,
        public string $name,
        public int $release,
        public string $releasePrecision,
        public int $totalTracks,
        public array $availableMarkets,
        /** @var array{href: string, id: string, name: string, type: string, uri: string}[] */
        public array $artists,
        public ?string $upc,
        public array $images,
        public array $copyrights,
        public array $externalIds,
        public array $genres,
        public ?string $label,
        public int $popularity,
    ) {}
}
