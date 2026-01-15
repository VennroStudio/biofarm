<?php

declare(strict_types=1);

namespace App\Modules\Command\PlaylistTranslate\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $playlistId,
        #[Assert\NotBlank]
        public string $lang,
        #[Assert\NotBlank]
        public string $name,
        public string $description,
        public ?string $filePath = null
    ) {}
}
