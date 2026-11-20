<?php

declare(strict_types=1);

namespace App\Modules\Page\Service;

use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PageNavigationTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly PageNavigationProvider $navigation,
    ) {}

    /**
     * @return list<TwigFunction>
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('cms_header_pages', $this->navigation->headerPages(...)),
            new TwigFunction('cms_footer_pages', $this->navigation->footerPages(...)),
        ];
    }
}
