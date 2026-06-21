<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Auth\Login;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginCommand
{
    private const int PASSWORD_MAX_LENGTH = 64;

    public function __construct(
        #[Assert\NotBlank(message: 'validation.email_required')]
        #[Assert\Email(message: 'validation.email_invalid')]
        public string $email,
        #[Assert\NotBlank(message: 'validation.password_required')]
        #[Assert\Length(max: self::PASSWORD_MAX_LENGTH, maxMessage: 'validation.password_too_long')]
        public string $password,
    ) {}
}
