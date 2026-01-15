<?php

declare(strict_types=1);

namespace App\Modules\Entity\BlogPost;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: BlogPost::DB_NAME)]
#[ORM\UniqueConstraint(name: 'UNIQUE_SLUG', columns: ['slug'])]
#[ORM\Index(fields: ['categoryId'], name: 'IDX_CATEGORY')]
#[ORM\Index(fields: ['isPublished'], name: 'IDX_PUBLISHED')]
final class BlogPost
{
    public const DB_NAME = 'biofarm_blog_posts';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 500)]
    private string $excerpt;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', length: 500)]
    private string $image;

    #[ORM\Column(type: 'string', length: 50)]
    private string $categoryId;

    #[ORM\Column(type: 'integer')]
    private int $authorId;

    #[ORM\Column(type: 'integer')]
    private int $readTime; // minutes

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isPublished = false;

    #[ORM\Column(type: 'integer')]
    private int $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $updatedAt = null;

    private function __construct(
        string $slug,
        string $title,
        string $excerpt,
        string $content,
        string $image,
        string $categoryId,
        int $authorId,
        int $readTime,
        bool $isPublished = false,
    ) {
        $this->slug = $slug;
        $this->title = $title;
        $this->excerpt = $excerpt;
        $this->content = $content;
        $this->image = $image;
        $this->categoryId = $categoryId;
        $this->authorId = $authorId;
        $this->readTime = $readTime;
        $this->isPublished = $isPublished;
        $this->createdAt = time();
    }

    public static function create(
        string $slug,
        string $title,
        string $excerpt,
        string $content,
        string $image,
        string $categoryId,
        int $authorId,
        int $readTime,
        bool $isPublished = false,
    ): self {
        return new self(
            slug: $slug,
            title: $title,
            excerpt: $excerpt,
            content: $content,
            image: $image,
            categoryId: $categoryId,
            authorId: $authorId,
            readTime: $readTime,
            isPublished: $isPublished,
        );
    }

    public function edit(
        string $title,
        string $excerpt,
        string $content,
        string $image,
        string $categoryId,
        int $readTime,
        bool $isPublished = false,
        ?string $slug = null,
    ): void {
        $this->title = $title;
        $this->excerpt = $excerpt;
        $this->content = $content;
        $this->image = $image;
        $this->categoryId = $categoryId;
        $this->readTime = $readTime;
        $this->isPublished = $isPublished;
        if ($slug !== null) {
            $this->slug = $slug;
        }
        $this->updatedAt = time();
    }

    public function getId(): int
    {
        if (null === $this->id) {
            throw new DomainException('Id not set');
        }
        return (int)$this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function getReadTime(): int
    {
        return $this->readTime;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }
}
