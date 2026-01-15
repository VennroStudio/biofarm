<?php

declare(strict_types=1);

namespace App\Modules\Command\SpotifyToken\Refresh;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $id,
        #[Assert\NotBlank]
        public string $accessToken,
    ) {}
}
