<?php

declare(strict_types=1);

namespace App\Modules\Command\Apple\RefreshTrack;

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
        public ?string $isrc,
        #[Assert\NotBlank]
        public string $name,
        public string $artists,
        public ?string $composers,
        #[Assert\NotBlank]
        public int $duration,
        public ?array $genreNames,
        public ?array $attributes,
    ) {}
}
