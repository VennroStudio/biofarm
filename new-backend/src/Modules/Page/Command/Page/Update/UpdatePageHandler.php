<?php

declare(strict_types=1);

namespace App\Modules\Page\Command\Page\Update;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Modules\Page\Entity\Page\Page;
use App\Modules\Page\Entity\Page\PageRepository;
use App\Modules\Page\Service\PageSlugPathNormalizer;
use App\Modules\Page\Service\PageTemplateCatalog;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class UpdatePageHandler
{
    public function __construct(
        private PageRepository $pages,
        private PageTemplateCatalog $templates,
        private PageSlugPathNormalizer $slugPathNormalizer,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function handle(UpdatePageCommand $command): void
    {
        $page = $this->pages->getById($command->pageId);

        if ($page->pageType === Page::TYPE_SYSTEM) {
            $page->editSystemSeo(
                title: trim($command->title),
                h1: $this->nullable($command->h1),
                seoTitle: $this->nullable($command->seoTitle),
                seoDescription: $this->nullable($command->seoDescription),
                ogTitle: $this->nullable($command->ogTitle),
                ogDescription: $this->nullable($command->ogDescription),
                ogImage: $this->nullable($command->ogImage),
                ogImageAlt: $this->nullable($command->ogImageAlt),
                isPublished: $command->isPublished,
                isIndexable: $command->isIndexable,
                showInSitemap: $command->showInSitemap,
                showInHeader: $command->showInHeader,
                showInFooter: $command->showInFooter,
                sortOrder: $command->sortOrder,
            );
        } else {
            $slugPath = $this->slugPathNormalizer->normalize((string)$command->slugPath);
            $template = trim((string)$command->template);
            $this->templates->assertExists($template);
            $this->assertSlugFree($slugPath, $page->id);

            $page->editCustom(
                slugPath: $slugPath,
                template: $template,
                title: trim($command->title),
                h1: $this->nullable($command->h1),
                content: $this->nullable($command->content),
                excerpt: $this->nullable($command->excerpt),
                seoTitle: $this->nullable($command->seoTitle),
                seoDescription: $this->nullable($command->seoDescription),
                ogTitle: $this->nullable($command->ogTitle),
                ogDescription: $this->nullable($command->ogDescription),
                ogImage: $this->nullable($command->ogImage),
                ogImageAlt: $this->nullable($command->ogImageAlt),
                isPublished: $command->isPublished,
                isIndexable: $command->isIndexable,
                showInSitemap: $command->showInSitemap,
                showInHeader: $command->showInHeader,
                showInFooter: $command->showInFooter,
                sortOrder: $command->sortOrder,
                publishedAt: $this->publishedAt($command->publishedAt),
            );
        }

        $this->clearCache();
        $this->flusher->flush();
    }

    private function assertSlugFree(string $slugPath, int $pageId): void
    {
        $existing = $this->pages->findBySlugPath($slugPath);
        if ($existing !== null && $existing->id !== $pageId) {
            throw new DomainExceptionModule('page', 'error.page_slug_already_exists', 8);
        }
    }

    private function nullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function publishedAt(?string $publishedAt): ?DateTimeImmutable
    {
        if ($publishedAt === null || trim($publishedAt) === '') {
            return null;
        }

        return new DateTimeImmutable($publishedAt);
    }

    private function clearCache(): void
    {
        $this->cacher->deleteTag('pages');
        $this->cacher->deleteTag('seo');
    }
}
