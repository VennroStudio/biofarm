<?php

declare(strict_types=1);

namespace App\Modules\Command\User\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank]
        public string $name,
        #[Assert\NotBlank]
        public string $passwordHash,
        public ?string $phone = null,
        public ?string $referredBy = null,
        public bool $isPartner = false,
        public bool $isActive = true,
    ) {}
}
