<?php

declare(strict_types=1);

namespace App\Modules\Setting\Entity\SiteSetting;

interface SiteSettingRepository
{
    public function add(SiteSetting $setting): void;

    public function getByKey(string $key): SiteSetting;

    public function findByKey(string $key): ?SiteSetting;
}
