<?php

declare(strict_types=1);

namespace App\Components\Microservice\Storage\StorageService;

use App\Components\Microservice\Storage\StorageService\DTO\StorageHostDTO;

interface StorageHostClient
{
    public function getByRandom(?string $capability = null): StorageHostDTO;

    public function getByHost(string $host): StorageHostDTO;

    public function getUploadURL(string $type, ?string $region = null, ?string $fileId = null): string;
}
