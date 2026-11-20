<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Auth\PasswordReset;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PasswordResetCommand
{
    private const int EMAIL_MAX_LENGTH = 255;

    public function __construct(
        #[Assert\NotBlank(message: 'validation.email_required')]
        #[Assert\Email(message: 'validation.email_invalid')]
        #[Assert\Length(max: self::EMAIL_MAX_LENGTH, maxMessage: 'validation.email_too_long')]
        public string $email,
        public string $locale = 'ru',
    ) {}
}
