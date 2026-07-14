<?php

declare(strict_types=1);

namespace App\Modules\Page\Service;

use App\Components\Seo\JsonLdFactory;
use App\Components\Seo\SeoUrlGenerator;
use App\Http\View\PageMetaView;
use App\Modules\Page\Query\Page\GetBySlugPath\PageGetBySlugPathFetcher;
use App\Modules\Page\Query\Page\GetBySlugPath\PageGetBySlugPathQuery;
use App\Modules\Page\Query\Page\GetBySystemKey\PageGetBySystemKeyFetcher;
use App\Modules\Page\Query\Page\GetBySystemKey\PageGetBySystemKeyQuery;
use App\Modules\Page\ReadModel\Page\PageDetails;
use Doctrine\DBAL\Exception;

final readonly class PageSeoProvider
{
    public function __construct(
        private PageGetBySystemKeyFetcher $systemPages,
        private PageGetBySlugPathFetcher $customPages,
        private SeoUrlGenerator $urls,
        private JsonLdFactory $jsonLd,
    ) {}

    /**
     * @throws Exception
     */
    public function applySystem(string $systemKey, PageMetaView $fallback): PageMetaView
    {
        $page = $this->systemPages->fetch(new PageGetBySystemKeyQuery($systemKey));
        if ($page === null || !$page->isPublished) {
            return $fallback;
        }

        $robots = $page->isIndexable && $fallback->robots !== 'noindex, follow'
            ? $fallback->robots
            : 'noindex, follow';

        return new PageMetaView(
            title: $this->value($page->seoTitle, $fallback->title),
            description: $this->value($page->seoDescription, $fallback->description),
            canonicalUrl: $fallback->canonicalUrl,
            robots: $robots,
            ogTitle: $this->value($page->ogTitle, $fallback->ogTitle ?? $fallback->title),
            ogDescription: $this->value($page->ogDescription, $fallback->ogDescription ?? $fallback->description),
            ogImage: $this->absoluteOrNull($page->ogImage) ?? $fallback->ogImage,
            ogImageAlt: $this->value($page->ogImageAlt, $fallback->ogImageAlt ?? $fallback->title),
            ogType: $fallback->ogType,
            jsonLd: $fallback->jsonLd,
        );
    }

    /**
     * @throws Exception
     */
    public function systemMeta(
        string $systemKey,
        string $path,
        string $title,
        string $description,
        string $ogType = 'website',
        string $robots = 'index, follow',
    ): PageMetaView
    {
        return $this->applySystem($systemKey, new PageMetaView(
            title: $title,
            description: $description,
            canonicalUrl: $this->urls->absolute($path),
            robots: $robots,
            ogTitle: $title,
            ogDescription: $description,
            ogImage: $this->urls->absolute('/assets/images/og/default.jpg'),
            ogImageAlt: $title,
            ogType: $ogType,
            jsonLd: [
                $this->jsonLd->breadcrumbs([
                    ['name' => 'Главная', 'url' => '/'],
                    ['name' => $title, 'url' => $path],
                ]),
                $this->jsonLd->webPage($title, $description, $path),
            ],
        ));
    }

    /**
     * @throws Exception
     */
    public function customBySlugPath(string $slugPath): ?PageDetails
    {
        return $this->customPages->fetch(new PageGetBySlugPathQuery($slugPath));
    }

    public function customMeta(PageDetails $page): PageMetaView
    {
        $path = '/' . ltrim((string)$page->slugPath, '/');
        $title = $this->value($page->seoTitle, $page->title . ' — БИОФАРМ');
        $description = $this->value($page->seoDescription, $page->excerpt ?? 'Информационная страница БИОФАРМ.');
        $h1 = $this->value($page->h1, $page->title);

        return new PageMetaView(
            title: $title,
            description: $description,
            canonicalUrl: $this->urls->absolute($path),
            robots: $page->isIndexable ? 'index, follow' : 'noindex, follow',
            ogTitle: $this->value($page->ogTitle, $title),
            ogDescription: $this->value($page->ogDescription, $description),
            ogImage: $this->absoluteOrNull($page->ogImage),
            ogImageAlt: $this->value($page->ogImageAlt, $h1),
            ogType: 'website',
            jsonLd: [
                $this->jsonLd->breadcrumbs([
                    ['name' => 'Главная', 'url' => '/'],
                    ['name' => $h1, 'url' => $path],
                ]),
                $this->jsonLd->webPage($h1, $description, $path),
            ],
        );
    }

    public function missingMeta(string $slugPath): PageMetaView
    {
        return new PageMetaView(
            title: 'Страница не найдена — БИОФАРМ',
            description: 'Запрошенная страница не найдена.',
            canonicalUrl: $this->urls->absolute('/' . ltrim($slugPath, '/')),
            robots: 'noindex, follow',
        );
    }

    private function value(?string $value, string $fallback): string
    {
        $value = $value !== null ? trim($value) : '';

        return $value !== '' ? $value : $fallback;
    }

    private function absoluteOrNull(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return $this->urls->absolute($path);
    }
}
