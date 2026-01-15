<?php

declare(strict_types=1);

namespace App\Components\NeteaseGrab\Entities;

class Artist
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
