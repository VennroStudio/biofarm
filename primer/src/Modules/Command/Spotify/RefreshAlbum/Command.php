<?php

declare(strict_types=1);

namespace App\Modules\Command\Spotify\RefreshAlbum;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        public ?int $artistId,
        /** @var string[] */
        public array $spotifyArtistIds,
        #[Assert\NotBlank]
        public string $albumId,
        #[Assert\NotBlank]
        public string $type,
        #[Assert\NotBlank]
        public string $name,
        #[Assert\NotBlank]
        public int $releasedAt,
        #[Assert\NotBlank]
        public string $releasedPrecision,
        #[Assert\NotBlank]
        public int $totalTracks,
        public array $availableMarkets,
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
