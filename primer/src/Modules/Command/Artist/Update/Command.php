<?php

declare(strict_types=1);

namespace App\Modules\Command\Artist\Update;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $artistId,
        #[Assert\NotBlank]
        public string $description,
        #[Assert\NotBlank]
        public string $loName,
        public string $loDescription,
        #[Assert\NotBlank]
        public int $loCategoryId,
    ) {}
}
