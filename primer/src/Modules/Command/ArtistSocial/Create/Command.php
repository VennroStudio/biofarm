<?php

declare(strict_types=1);

namespace App\Modules\Command\ArtistSocial\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $artistId,
        #[Assert\NotBlank]
        public string $url,
        public ?string $description,
    ) {}
}
