<?php

declare(strict_types=1);

namespace App\Modules\Command\Apple\RefreshAlbum;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        public ?int $artistId,
        #[Assert\NotBlank]
        public string $albumId,
        public ?string $upc,
        #[Assert\NotBlank]
        public string $name,
        public bool $isCompilation,
        public bool $isSingle,
        public ?int $releasedAt,
        public int $totalTracks,
        public string $artists,
        public ?string $image,
        public ?string $video,
        public ?string $copyrights,
        public ?string $label,
        public ?array $genreNames,
        public ?array $attributes,
    ) {}
}
