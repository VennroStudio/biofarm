<?php

declare(strict_types=1);

namespace App\Modules\Blog\ReadModel\BlogPost;

use App\Components\ReadModel\FromRowsTrait;
use App\Modules\Blog\ReadModel\BlogPost\Interface\BlogPostModelInterface;
use Override;

final readonly class BlogPostDetails implements BlogPostModelInterface
{
    use FromRowsTrait;

    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public ?string $h1,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public string $excerpt,
        public string $content,
        public string $image,
        public ?string $imageAlt,
        public string $categoryId,
        public string $authorName,
        public int $readTime,
        public bool $isPublished,
        public string $createdAt,
        public ?string $publishedAt,
        public ?string $updatedAt,
    ) {}

    public static function fields(): array
    {
        return [
            'id'           => 'id',
            'slug'         => 'slug',
            'title'        => 'title',
            'h1'           => 'h1',
            'seo_title'    => 'seo_title',
            'seo_description' => 'seo_description',
            'excerpt'      => 'excerpt',
            'content'      => 'content',
            'image'        => 'image',
            'image_alt'    => 'image_alt',
            'category_id'  => 'category_id',
            'author_name'  => 'author_name',
            'read_time'    => 'read_time',
            'is_published' => 'is_published',
            'created_at'   => 'created_at',
            'published_at' => 'published_at',
            'updated_at'   => 'updated_at',
        ];
    }

    /**
     * @param array{
     *     id: int,
     *     slug: string,
     *     title: string,
     *     h1: string|null,
     *     seo_title: string|null,
     *     seo_description: string|null,
     *     excerpt: string,
     *     content: string,
     *     image: string,
     *     image_alt: string|null,
     *     category_id: string,
     *     author_name: string,
     *     read_time: int,
     *     is_published: bool|int|string,
     *     created_at: string,
     *     published_at: string|null,
     *     updated_at: string|null
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            slug: $row['slug'],
            title: $row['title'],
            h1: $row['h1'],
            seoTitle: $row['seo_title'],
            seoDescription: $row['seo_description'],
            excerpt: $row['excerpt'],
            content: $row['content'],
            image: $row['image'],
            imageAlt: $row['image_alt'],
            categoryId: $row['category_id'],
            authorName: $row['author_name'],
            readTime: (int)$row['read_time'],
            isPublished: (bool)(int)$row['is_published'],
            createdAt: $row['created_at'],
            publishedAt: $row['published_at'],
            updatedAt: $row['updated_at'],
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
            'id'           => $this->id,
            'slug'         => $this->slug,
            'title'        => $this->title,
            'h1'           => $this->h1,
            'seo_title'    => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'excerpt'      => $this->excerpt,
            'content'      => $this->content,
            'image'        => $this->image,
            'image_alt'    => $this->imageAlt,
            'date'         => substr($this->createdAt, 0, 10),
            'category_id'  => $this->categoryId,
            'author_name'  => $this->authorName,
            'read_time'    => $this->readTime,
            'is_published' => $this->isPublished,
            'created_at'   => $this->createdAt,
            'published_at' => $this->publishedAt,
            'updated_at'   => $this->updatedAt,
        ];
    }
}
