<?php

declare(strict_types=1);

namespace App\Modules\Page\Command\Page\Update;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdatePageCommand
{
    public function __construct(
        #[Assert\NotBlank]
        public int $pageId,
        #[Assert\NotBlank(message: 'validation.page_title_required')]
        public string $title,
        public ?string $slugPath = null,
        public ?string $template = null,
        public ?string $h1 = null,
        public ?string $content = null,
        public ?string $excerpt = null,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,
        public ?string $ogImageAlt = null,
        public ?string $publishedAt = null,
        public bool $isPublished = true,
        public bool $isIndexable = true,
        public bool $showInSitemap = true,
        public bool $showInHeader = false,
        public bool $showInFooter = false,
        public int $sortOrder = 0,
    ) {}
}
