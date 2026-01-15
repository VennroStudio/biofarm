<?php

declare(strict_types=1);

namespace App\Modules\Command\PossibleArtist\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        public ?int $artistId,
        public ?int $playlistId,
        public ?string $spotifyId,
        public ?string $appleId,
        public ?string $tidalId,
    ) {}
}
