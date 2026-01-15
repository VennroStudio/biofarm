<?php

declare(strict_types=1);

namespace App\Modules\Command\Settings\Create;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public string $key,
        #[Assert\NotBlank]
        public string $value,
    ) {}
}
