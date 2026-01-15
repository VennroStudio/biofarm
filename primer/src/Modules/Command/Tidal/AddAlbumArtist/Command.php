<?php

declare(strict_types=1);

namespace App\Modules\Command\Tidal\AddAlbumArtist;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $albumId,
        #[Assert\NotBlank]
        public int $artistId,
    ) {}
}
