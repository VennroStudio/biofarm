<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Auth\PasswordResetConfirm;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PasswordResetConfirmCommand
{
    private const int PASSWORD_MIN_LENGTH = 8;
    private const int PASSWORD_MAX_LENGTH = 64;

    public function __construct(
        #[Assert\NotBlank(message: 'validation.token_required')]
        #[Assert\Length(max: 255)]
        public string $token,
        #[Assert\NotBlank(message: 'validation.password_required')]
        #[Assert\Length(
            min: self::PASSWORD_MIN_LENGTH,
            max: self::PASSWORD_MAX_LENGTH,
            minMessage: 'validation.password_too_short',
            maxMessage: 'validation.password_too_long',
        )]
        #[Assert\Regex(
            pattern: '/[A-Z]/',
            message: 'validation.password_uppercase',
        )]
        #[Assert\Regex(
            pattern: '/[a-z]/',
            message: 'validation.password_lowercase',
        )]
        #[Assert\Regex(
            pattern: '/[0-9]/',
            message: 'validation.password_digit',
        )]
        #[Assert\Regex(
            pattern: '/[\W_]/',
            message: 'validation.password_special',
        )]
        public string $password,
    ) {}
}
