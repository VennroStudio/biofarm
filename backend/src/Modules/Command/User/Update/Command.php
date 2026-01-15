<?php

declare(strict_types=1);

namespace App\Modules\Command\User\Update;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $userId,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $cardNumber = null,
        public ?bool $isPartner = null,
        public ?bool $isActive = null,
    ) {}
}
