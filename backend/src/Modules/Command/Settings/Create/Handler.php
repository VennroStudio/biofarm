<?php

declare(strict_types=1);

namespace App\Modules\Command\Settings\Create;

use App\Modules\Entity\Settings\Settings;
use App\Modules\Entity\Settings\SettingsRepository;
use DomainException;

final readonly class Handler
{
    public function __construct(
        private SettingsRepository $settingsRepository,
    ) {}

    public function handle(Command $command): Settings
    {
        $existing = $this->settingsRepository->findByKey($command->key);

        if ($existing) {
            $existing->updateValue($command->value);
            return $existing;
        }

        $setting = Settings::create(
            key: $command->key,
            value: $command->value,
        );

        $this->settingsRepository->add($setting);

        return $setting;
    }
}
