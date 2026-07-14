<?php

declare(strict_types=1);

namespace App\Modules\Page\Command\Page\Create;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Modules\Page\Entity\Page\Page;
use App\Modules\Page\Entity\Page\PageRepository;
use App\Modules\Page\Service\PageSlugPathNormalizer;
use App\Modules\Page\Service\PageTemplateCatalog;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class CreatePageHandler
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
    public function handle(CreatePageCommand $command): int
    {
        $slugPath = $this->slugPathNormalizer->normalize($command->slugPath);
        $template = trim($command->template);
        $this->templates->assertExists($template);
        $this->assertSlugFree($slugPath);

        $page = Page::createCustom(
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

        $this->pages->add($page);
        $this->clearCache();
        $this->flusher->flush();

        return (int)$page->id;
    }

    private function assertSlugFree(string $slugPath): void
    {
        if ($this->pages->findBySlugPath($slugPath) !== null) {
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
