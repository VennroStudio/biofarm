<?php

declare(strict_types=1);

namespace App\Components\AppleGrab\Entities;

readonly class Artist
{
    public function __construct(
        public string $id,
        public string $type,
        public string $name,
        public ?string $avatar,
        public array $attributes,
    ) {}
}
