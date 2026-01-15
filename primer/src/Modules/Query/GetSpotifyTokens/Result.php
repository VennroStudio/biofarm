<?php

declare(strict_types=1);

namespace App\Modules\Query\GetSpotifyTokens;

final readonly class Result
{
    public function __construct(
        public int $id,
        public string $comment,
    ) {}
}
