<?php

declare(strict_types=1);

namespace App\Modules\Command\Withdrawal\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $withdrawalId,
        #[Assert\NotBlank]
        public int $userId,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $amount,
        public string $status = 'pending',
    ) {}
}
