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
        public string $excerpt,
        public string $content,
        public string $image,
        public string $categoryId,
        public string $authorName,
        public int $readTime,
        public bool $isPublished,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fields(): array
    {
        return [
            'id'           => 'id',
            'slug'         => 'slug',
            'title'        => 'title',
            'excerpt'      => 'excerpt',
            'content'      => 'content',
            'image'        => 'image',
            'category_id'  => 'category_id',
            'author_name'  => 'author_name',
            'read_time'    => 'read_time',
            'is_published' => 'is_published',
            'created_at'   => 'created_at',
            'updated_at'   => 'updated_at',
        ];
    }

    /**
     * @param array{
     *     id: int,
     *     slug: string,
     *     title: string,
     *     excerpt: string,
     *     content: string,
     *     image: string,
     *     category_id: string,
     *     author_name: string,
     *     read_time: int,
     *     is_published: bool|int|string,
     *     created_at: string,
     *     updated_at: string|null
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            slug: $row['slug'],
            title: $row['title'],
            excerpt: $row['excerpt'],
            content: $row['content'],
            image: $row['image'],
            categoryId: $row['category_id'],
            authorName: $row['author_name'],
            readTime: (int)$row['read_time'],
            isPublished: (bool)(int)$row['is_published'],
            createdAt: $row['created_at'],
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
            'excerpt'      => $this->excerpt,
            'content'      => $this->content,
            'image'        => $this->image,
            'date'         => substr($this->createdAt, 0, 10),
            'category_id'  => $this->categoryId,
            'author_name'  => $this->authorName,
            'read_time'    => $this->readTime,
            'is_published' => $this->isPublished,
            'created_at'   => $this->createdAt,
            'updated_at'   => $this->updatedAt,
        ];
    }
}
