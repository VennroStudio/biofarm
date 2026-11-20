<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Auth\Logout;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LogoutCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.refresh_token_required')]
        public string $refreshToken,
    ) {}
}
