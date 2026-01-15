<?php

declare(strict_types=1);

namespace App\Components\TidalGrab\Entities;

readonly class Token
{
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
    ) {}
}
