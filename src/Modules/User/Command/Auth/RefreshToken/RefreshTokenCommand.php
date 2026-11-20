<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Auth\RefreshToken;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RefreshTokenCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.refresh_token_required')]
        public string $refreshToken,
    ) {}
}
