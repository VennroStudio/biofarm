<?php

declare(strict_types=1);

namespace App\Modules\Command\Spotify\RefreshTrack;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        public ?int $albumId,
        #[Assert\NotBlank]
        public string $trackId,
        public ?string $trackLinkedId,
        #[Assert\NotBlank]
        public int $diskNumber,
        #[Assert\NotBlank]
        public int $trackNumber,
        #[Assert\NotBlank]
        public string $name,
        public bool $explicit,
        public array $artists,
        public ?string $isrc,
        #[Assert\NotBlank]
        public int $duration,
        #[Assert\NotBlank]
        public string $type,
        #[Assert\NotBlank]
        public bool $isLocal,
        #[Assert\NotBlank]
        public array $availableMarkets
    ) {}
}
