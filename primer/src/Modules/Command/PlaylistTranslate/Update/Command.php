<?php

declare(strict_types=1);

namespace App\Modules\Command\PlaylistTranslate\Update;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $playlistId,
        #[Assert\NotBlank]
        public int $translateId,
        #[Assert\NotBlank]
        public string $name,
        public string $description,
        public ?string $filePath = null
    ) {}
}
