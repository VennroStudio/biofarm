<?php

declare(strict_types=1);

namespace App\Modules\Command\BlogPost\Delete;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public int $postId,
    ) {}
}
