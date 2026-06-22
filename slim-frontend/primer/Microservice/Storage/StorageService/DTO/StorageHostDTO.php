<?php

declare(strict_types=1);

namespace App\Components\Microservice\Storage\StorageService\DTO;

final readonly class StorageHostDTO
{
    public function __construct(
        public int $id,
        public string $host,
        public string $secret,
        public string $region,
        public int $weight,
        public string $capabilities,
        public bool $isActive,
    ) {}
}
