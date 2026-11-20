<?php

declare(strict_types=1);

namespace App\Components\Setting;

use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class SiteSettingsTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly SiteSettings $settings,
    ) {}

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('site_setting', $this->settings->get(...)),
            new TwigFunction('site_feature', $this->settings->bool(...)),
        ];
    }
}
