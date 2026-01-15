<?php

declare(strict_types=1);

namespace App\Modules\Command\Spotify\InactiveToken;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $accessToken,
        #[Assert\NotBlank]
        public string $error,
    ) {}
}
