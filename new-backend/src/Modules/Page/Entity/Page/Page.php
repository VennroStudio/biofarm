<?php

declare(strict_types=1);

namespace App\Modules\Page\Entity\Page;

use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pages')]
#[ORM\UniqueConstraint(name: 'uniq_pages_system_key', columns: ['system_key'])]
#[ORM\UniqueConstraint(name: 'uniq_pages_slug_path', columns: ['slug_path'])]
#[ORM\Index(name: 'idx_pages_page_type', columns: ['page_type'])]
#[ORM\Index(name: 'idx_pages_published', columns: ['is_published'])]
#[ORM\Index(name: 'idx_pages_sitemap', columns: ['show_in_sitemap'])]
#[ORM\Index(name: 'idx_pages_sort_order', columns: ['sort_order'])]
class Page
{
    public const string TYPE_SYSTEM = 'system';
    public const string TYPE_CUSTOM = 'custom';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(name: 'page_type', type: Types::STRING, length: 20)]
    private(set) string $pageType;

    #[ORM\Column(name: 'system_key', type: Types::STRING, length: 100, nullable: true)]
    private(set) ?string $systemKey;

    #[ORM\Column(name: 'slug_path', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $slugPath;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private(set) ?string $template;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $title;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $h1;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private(set) ?string $content;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $excerpt;

    #[ORM\Column(name: 'seo_title', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $seoTitle;

    #[ORM\Column(name: 'seo_description', type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $seoDescription;

    #[ORM\Column(name: 'og_title', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $ogTitle;

    #[ORM\Column(name: 'og_description', type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $ogDescription;

    #[ORM\Column(name: 'og_image', type: Types::STRING, length: 512, nullable: true)]
    private(set) ?string $ogImage;

    #[ORM\Column(name: 'og_image_alt', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $ogImageAlt;

    #[ORM\Column(name: 'is_published', type: Types::BOOLEAN, options: ['default' => true])]
    private(set) bool $isPublished;

    #[ORM\Column(name: 'is_indexable', type: Types::BOOLEAN, options: ['default' => true])]
    private(set) bool $isIndexable;

    #[ORM\Column(name: 'show_in_sitemap', type: Types::BOOLEAN, options: ['default' => true])]
    private(set) bool $showInSitemap;

    #[ORM\Column(name: 'show_in_header', type: Types::BOOLEAN, options: ['default' => false])]
    private(set) bool $showInHeader;

    #[ORM\Column(name: 'show_in_footer', type: Types::BOOLEAN, options: ['default' => false])]
    private(set) bool $showInFooter;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0])]
    private(set) int $sortOrder;

    #[ORM\Column(name: 'published_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $publishedAt;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $deletedAt = null;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(
        string $pageType,
        ?string $systemKey,
        ?string $slugPath,
        ?string $template,
        string $title,
        ?string $h1,
        ?string $content,
        ?string $excerpt,
        ?string $seoTitle,
        ?string $seoDescription,
        ?string $ogTitle,
        ?string $ogDescription,
        ?string $ogImage,
        ?string $ogImageAlt,
        bool $isPublished,
        bool $isIndexable,
        bool $showInSitemap,
        bool $showInHeader,
        bool $showInFooter,
        int $sortOrder,
        ?DateTimeImmutable $publishedAt,
    ) {
        $this->pageType = $pageType;
        $this->systemKey = $systemKey;
        $this->slugPath = $slugPath;
        $this->template = $template;
        $this->title = $title;
        $this->h1 = $h1;
        $this->content = $content;
        $this->excerpt = $excerpt;
        $this->seoTitle = $seoTitle;
        $this->seoDescription = $seoDescription;
        $this->ogTitle = $ogTitle;
        $this->ogDescription = $ogDescription;
        $this->ogImage = $ogImage;
        $this->ogImageAlt = $ogImageAlt;
        $this->isPublished = $isPublished;
        $this->isIndexable = $isIndexable;
        $this->showInSitemap = $showInSitemap;
        $this->showInHeader = $showInHeader;
        $this->showInFooter = $showInFooter;
        $this->sortOrder = $sortOrder;
        $this->createdAt = UtcClock::now();
        $this->publishedAt = $publishedAt ?? ($isPublished ? $this->createdAt : null);
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function createCustom(
        string $slugPath,
        string $template,
        string $title,
        ?string $h1,
        ?string $content,
        ?string $excerpt,
        ?string $seoTitle,
        ?string $seoDescription,
        ?string $ogTitle,
        ?string $ogDescription,
        ?string $ogImage,
        ?string $ogImageAlt,
        bool $isPublished,
        bool $isIndexable,
        bool $showInSitemap,
        bool $showInHeader,
        bool $showInFooter,
        int $sortOrder,
        ?DateTimeImmutable $publishedAt,
    ): self {
        return new self(
            pageType: self::TYPE_CUSTOM,
            systemKey: null,
            slugPath: $slugPath,
            template: $template,
            title: $title,
            h1: $h1,
            content: $content,
            excerpt: $excerpt,
            seoTitle: $seoTitle,
            seoDescription: $seoDescription,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImage: $ogImage,
            ogImageAlt: $ogImageAlt,
            isPublished: $isPublished,
            isIndexable: $isIndexable,
            showInSitemap: $showInSitemap,
            showInHeader: $showInHeader,
            showInFooter: $showInFooter,
            sortOrder: $sortOrder,
            publishedAt: $publishedAt,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function editCustom(
        string $slugPath,
        string $template,
        string $title,
        ?string $h1,
        ?string $content,
        ?string $excerpt,
        ?string $seoTitle,
        ?string $seoDescription,
        ?string $ogTitle,
        ?string $ogDescription,
        ?string $ogImage,
        ?string $ogImageAlt,
        bool $isPublished,
        bool $isIndexable,
        bool $showInSitemap,
        bool $showInHeader,
        bool $showInFooter,
        int $sortOrder,
        ?DateTimeImmutable $publishedAt,
    ): void {
        $this->assertNotDeleted();
        $this->assertCustom();

        $this->slugPath = $slugPath;
        $this->template = $template;
        $this->changeCommon(
            title: $title,
            h1: $h1,
            content: $content,
            excerpt: $excerpt,
            seoTitle: $seoTitle,
            seoDescription: $seoDescription,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImage: $ogImage,
            ogImageAlt: $ogImageAlt,
            isPublished: $isPublished,
            isIndexable: $isIndexable,
            showInSitemap: $showInSitemap,
            showInHeader: $showInHeader,
            showInFooter: $showInFooter,
            sortOrder: $sortOrder,
            publishedAt: $publishedAt,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function editSystemSeo(
        string $title,
        ?string $h1,
        ?string $seoTitle,
        ?string $seoDescription,
        ?string $ogTitle,
        ?string $ogDescription,
        ?string $ogImage,
        ?string $ogImageAlt,
        bool $isPublished,
        bool $isIndexable,
        bool $showInSitemap,
        bool $showInHeader,
        bool $showInFooter,
        int $sortOrder,
    ): void {
        $this->assertNotDeleted();

        if ($this->pageType !== self::TYPE_SYSTEM) {
            throw new DomainExceptionModule('page', 'error.page_is_not_system', 4);
        }

        $this->changeCommon(
            title: $title,
            h1: $h1,
            content: $this->content,
            excerpt: $this->excerpt,
            seoTitle: $seoTitle,
            seoDescription: $seoDescription,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImage: $ogImage,
            ogImageAlt: $ogImageAlt,
            isPublished: $isPublished,
            isIndexable: $isIndexable,
            showInSitemap: $showInSitemap,
            showInHeader: $showInHeader,
            showInFooter: $showInFooter,
            sortOrder: $sortOrder,
            publishedAt: $this->publishedAt,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markDeleted(): void
    {
        $this->assertNotDeleted();
        $this->assertCustom();
        $deletedAt = UtcClock::now();
        $this->releaseSlugPath($deletedAt);
        $this->deletedAt = $deletedAt;
        $this->isPublished = false;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    private function changeCommon(
        string $title,
        ?string $h1,
        ?string $content,
        ?string $excerpt,
        ?string $seoTitle,
        ?string $seoDescription,
        ?string $ogTitle,
        ?string $ogDescription,
        ?string $ogImage,
        ?string $ogImageAlt,
        bool $isPublished,
        bool $isIndexable,
        bool $showInSitemap,
        bool $showInHeader,
        bool $showInFooter,
        int $sortOrder,
        ?DateTimeImmutable $publishedAt,
    ): void {
        $this->title = $title;
        $this->h1 = $h1;
        $this->content = $content;
        $this->excerpt = $excerpt;
        $this->seoTitle = $seoTitle;
        $this->seoDescription = $seoDescription;
        $this->ogTitle = $ogTitle;
        $this->ogDescription = $ogDescription;
        $this->ogImage = $ogImage;
        $this->ogImageAlt = $ogImageAlt;
        $this->isPublished = $isPublished;
        $this->isIndexable = $isIndexable;
        $this->showInSitemap = $showInSitemap;
        $this->showInHeader = $showInHeader;
        $this->showInFooter = $showInFooter;
        $this->sortOrder = $sortOrder;
        $this->publishedAt = $publishedAt ?? ($isPublished ? ($this->publishedAt ?? UtcClock::now()) : null);
        $this->touch();
    }

    private function releaseSlugPath(DateTimeImmutable $deletedAt): void
    {
        if ($this->slugPath === null || $this->slugPath === '') {
            return;
        }

        $suffix = '-deleted-' . ($this->id ?? 'new') . '-' . $deletedAt->format('YmdHis');
        $maxBaseLength = 255 - mb_strlen($suffix);
        $base = mb_substr($this->slugPath, 0, max(1, $maxBaseLength));
        $this->slugPath = $base . $suffix;
    }

    /**
     * @throws DateMalformedStringException
     */
    private function touch(): void
    {
        $this->updatedAt = UtcClock::now();
    }

    private function assertCustom(): void
    {
        if ($this->pageType !== self::TYPE_CUSTOM) {
            throw new DomainExceptionModule('page', 'error.system_page_is_locked', 3);
        }
    }

    private function assertNotDeleted(): void
    {
        if ($this->deletedAt !== null) {
            throw new DomainExceptionModule('page', 'error.page_is_deleted', 2);
        }
    }
}
