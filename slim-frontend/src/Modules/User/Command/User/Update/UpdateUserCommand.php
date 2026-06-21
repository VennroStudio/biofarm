<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\Update;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserCommand
{
    private const int NAME_MIN_LENGTH = 2;
    private const int NAME_MAX_LENGTH = 60;
    private const string NAME_PATTERN = "/^\\p{L}[\\p{L}\\s'\\-]*$/u";

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $userId,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
        #[Assert\NotBlank(message: 'validation.last_name_required')]
        #[Assert\Length(
            min: self::NAME_MIN_LENGTH,
            max: self::NAME_MAX_LENGTH,
            minMessage: 'validation.last_name_too_short',
            maxMessage: 'validation.last_name_too_long',
        )]
        #[Assert\Regex(pattern: self::NAME_PATTERN, message: 'validation.last_name_invalid')]
        public string $lastName,
        #[Assert\NotBlank(message: 'validation.first_name_required')]
        #[Assert\Length(
            min: self::NAME_MIN_LENGTH,
            max: self::NAME_MAX_LENGTH,
            minMessage: 'validation.first_name_too_short',
            maxMessage: 'validation.first_name_too_long',
        )]
        #[Assert\Regex(pattern: self::NAME_PATTERN, message: 'validation.first_name_invalid')]
        public string $firstName,
    ) {}
}
