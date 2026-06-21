<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Mailer\PasswordReset;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PasswordResetCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $token,
        #[Assert\NotBlank]
        public string $locale = 'ru',
    ) {}
}
