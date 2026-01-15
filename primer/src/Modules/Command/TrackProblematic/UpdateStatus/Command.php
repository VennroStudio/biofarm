<?php

declare(strict_types=1);

namespace App\Modules\Command\TrackProblematic\UpdateStatus;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Command
{
    public function __construct(
        #[Assert\NotBlank]
        public array|int $loTrackId,
        #[Assert\NotBlank]
        public int $status,
    ) {}
}
