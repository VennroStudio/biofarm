<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\Delete;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeleteUserCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $userId,
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $currentUserId,
        #[Assert\NotBlank]
        public int $currentUserRole,
    ) {}
}
