<?php

declare(strict_types=1);

namespace App\Components\NeteaseGrab\Entities;

readonly class Track
{
    public function __construct(
        public int $id,
        public string $name,
        public int $duration,
        public Album $album,
        /** @var Artist[] */
        public array $artists,
    ) {}
}
