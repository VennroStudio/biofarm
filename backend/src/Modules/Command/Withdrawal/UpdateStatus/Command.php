<?php

declare(strict_types=1);

namespace App\Modules\Command\Withdrawal\UpdateStatus;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $withdrawalId,
        #[Assert\NotBlank]
        public string $status,
        public ?string $processedBy = null,
    ) {}
}
