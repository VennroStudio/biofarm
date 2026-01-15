<?php

declare(strict_types=1);

namespace App\Modules\Command\Album\AddArtist;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $albumId,
        #[Assert\NotBlank]
        public int $artistId,
        public ?int $number,
    ) {}
}
