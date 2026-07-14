<?php

declare(strict_types=1);

namespace App\Modules\Page\ReadModel\Page;

use App\Components\ReadModel\FromRowsTrait;
use App\Components\ReadModel\ReadModelInterface;
use Override;

final readonly class PageDetails implements ReadModelInterface
{
    use FromRowsTrait;

    public function __construct(
        public int $id,
        public string $pageType,
        public ?string $systemKey,
        public ?string $slugPath,
        public ?string $template,
        public string $title,
        public ?string $h1,
        public ?string $content,
        public ?string $excerpt,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?string $ogTitle,
        public ?string $ogDescription,
        public ?string $ogImage,
        public ?string $ogImageAlt,
        public bool $isPublished,
        public bool $isIndexable,
        public bool $showInSitemap,
        public bool $showInHeader,
        public bool $showInFooter,
        public int $sortOrder,
        public ?string $publishedAt,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fields(): array
    {
        return [
            'id'               => 'id',
            'page_type'        => 'page_type',
            'system_key'       => 'system_key',
            'slug_path'        => 'slug_path',
            'template'         => 'template',
            'title'            => 'title',
            'h1'               => 'h1',
            'content'          => 'content',
            'excerpt'          => 'excerpt',
            'seo_title'        => 'seo_title',
            'seo_description'  => 'seo_description',
            'og_title'         => 'og_title',
            'og_description'   => 'og_description',
            'og_image'         => 'og_image',
            'og_image_alt'     => 'og_image_alt',
            'is_published'     => 'is_published',
            'is_indexable'     => 'is_indexable',
            'show_in_sitemap'  => 'show_in_sitemap',
            'show_in_header'   => 'show_in_header',
            'show_in_footer'   => 'show_in_footer',
            'sort_order'       => 'sort_order',
            'published_at'     => 'published_at',
            'created_at'       => 'created_at',
            'updated_at'       => 'updated_at',
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            pageType: (string)$row['page_type'],
            systemKey: self::nullableString($row['system_key']),
            slugPath: self::nullableString($row['slug_path']),
            template: self::nullableString($row['template']),
            title: (string)$row['title'],
            h1: self::nullableString($row['h1']),
            content: self::nullableString($row['content']),
            excerpt: self::nullableString($row['excerpt']),
            seoTitle: self::nullableString($row['seo_title']),
            seoDescription: self::nullableString($row['seo_description']),
            ogTitle: self::nullableString($row['og_title']),
            ogDescription: self::nullableString($row['og_description']),
            ogImage: self::nullableString($row['og_image']),
            ogImageAlt: self::nullableString($row['og_image_alt']),
            isPublished: (bool)(int)$row['is_published'],
            isIndexable: (bool)(int)$row['is_indexable'],
            showInSitemap: (bool)(int)$row['show_in_sitemap'],
            showInHeader: (bool)(int)$row['show_in_header'],
            showInFooter: (bool)(int)$row['show_in_footer'],
            sortOrder: (int)$row['sort_order'],
            publishedAt: self::nullableString($row['published_at']),
            createdAt: (string)$row['created_at'],
            updatedAt: self::nullableString($row['updated_at']),
        );
    }

    #[Override]
    public function getId(): int
    {
        return $this->id;
    }

    #[Override]
    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'page_type'       => $this->pageType,
            'system_key'      => $this->systemKey,
            'slug_path'       => $this->slugPath,
            'template'        => $this->template,
            'title'           => $this->title,
            'h1'              => $this->h1,
            'content'         => $this->content,
            'excerpt'         => $this->excerpt,
            'seo_title'       => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'og_title'        => $this->ogTitle,
            'og_description'  => $this->ogDescription,
            'og_image'        => $this->ogImage,
            'og_image_alt'    => $this->ogImageAlt,
            'is_published'    => $this->isPublished,
            'is_indexable'    => $this->isIndexable,
            'show_in_sitemap' => $this->showInSitemap,
            'show_in_header'  => $this->showInHeader,
            'show_in_footer'  => $this->showInFooter,
            'sort_order'      => $this->sortOrder,
            'published_at'    => $this->publishedAt,
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);

        return $value !== '' ? $value : null;
    }
}
