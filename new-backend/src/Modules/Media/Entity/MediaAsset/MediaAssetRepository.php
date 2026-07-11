<?php

declare(strict_types=1);

namespace App\Modules\Media\Entity\MediaAsset;

interface MediaAssetRepository
{
    public function add(MediaAsset $asset): void;

    public function remove(MediaAsset $asset): void;

    public function getById(int $id): MediaAsset;

    public function findById(int $id): ?MediaAsset;
}
