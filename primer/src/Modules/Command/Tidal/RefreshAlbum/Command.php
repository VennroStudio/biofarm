<?php

declare(strict_types=1);

namespace App\Modules\Command\Tidal\RefreshAlbum;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        public ?int $artistId,
        #[Assert\NotBlank]
        public string $albumId,
        #[Assert\NotBlank]
        public string $type,
        #[Assert\NotBlank]
        public string $barcodeId,
        #[Assert\NotBlank]
        public string $name,
        #[Assert\NotBlank]
        public array $artists,
        public ?array $images,
        public ?array $videos,
        public ?int $releasedAt,
        public int $totalTracks,
        public ?string $copyrights,
        public ?array $mediaMetadata,
        public ?array $properties,
    ) {}
}
