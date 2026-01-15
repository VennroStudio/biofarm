<?php

declare(strict_types=1);

namespace App\Modules\Command\Playlist\AddTrack;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $playlistId,
        #[Assert\NotBlank]
        public int $trackId,
        #[Assert\NotBlank]
        public int $number,
    ) {}
}
