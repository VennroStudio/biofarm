<?php

declare(strict_types=1);

namespace App\Modules\Query\Playlists\GetTranslatesArray;

final readonly class Result
{
    public function __construct(
        public int $id,
        public string $lang,
        public string $name,
        public ?string $photo,
        public ?string $description,
    ) {}
}
