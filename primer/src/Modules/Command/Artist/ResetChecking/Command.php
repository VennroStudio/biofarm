<?php

declare(strict_types=1);

namespace App\Modules\Command\Artist\ResetChecking;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $artistId,
    ) {}
}
