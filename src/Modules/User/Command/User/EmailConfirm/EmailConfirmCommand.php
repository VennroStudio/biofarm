<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\EmailConfirm;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class EmailConfirmCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.token_required')]
        public string $token,
    ) {}
}
