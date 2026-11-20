<?php

declare(strict_types=1);

namespace App\Modules\Page\Command\Page\Delete;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeletePageCommand
{
    public function __construct(
        #[Assert\NotBlank]
        public int $pageId,
    ) {}
}
