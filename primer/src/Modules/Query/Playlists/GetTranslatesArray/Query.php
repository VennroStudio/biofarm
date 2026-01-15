<?php

declare(strict_types=1);

namespace App\Modules\Query\Playlists\GetTranslatesArray;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class Query
{
    public function __construct(
        #[Assert\NotBlank]
        public int $playlistId,
    ) {}
}
