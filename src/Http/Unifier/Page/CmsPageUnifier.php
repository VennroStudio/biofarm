<?php

declare(strict_types=1);

namespace App\Http\Unifier\Page;

use App\Http\View\Page\CmsPageView;
use App\Modules\Page\Service\PageSeoProvider;
use App\Modules\Page\Service\PageTemplateCatalog;
use Doctrine\DBAL\Exception;

final readonly class CmsPageUnifier
{
    public function __construct(
        private PageSeoProvider $seo,
        private PageTemplateCatalog $templates,
    ) {}

    /**
     * @throws Exception
     */
    public function unify(string $slugPath): CmsPageView
    {
        $slugPath = trim($slugPath, '/');
        $page = $this->seo->customBySlugPath($slugPath);

        if ($page === null || !$page->isPublished || $page->template === null) {
            return new CmsPageView(
                meta: $this->seo->missingMeta($slugPath),
                page: null,
                template: 'pages/content/not-found.html.twig',
            );
        }

        return new CmsPageView(
            meta: $this->seo->customMeta($page),
            page: $page,
            template: $this->templates->twigTemplate($page->template),
        );
    }
}
