<?php

declare(strict_types=1);

namespace App\Modules\Query\Artists\GetArtistSocialsArray;

final readonly class Result
{
    public function __construct(
        public int $id,
        public int $type,
        public string $url,
        public ?string $description,
    ) {}
}
