<?php

declare(strict_types=1);

namespace App\Modules\Blog\Entity\BlogPost;

use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'blog_posts')]
#[ORM\UniqueConstraint(name: 'uniq_blog_posts_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_blog_posts_category_id', columns: ['category_id'])]
#[ORM\Index(name: 'idx_blog_posts_is_published', columns: ['is_published'])]
class BlogPost
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $slug;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $title;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private(set) string $excerpt;

    #[ORM\Column(type: Types::TEXT)]
    private(set) string $content;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private(set) string $image;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private(set) string $categoryId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $authorName;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $readTime;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private(set) bool $isPublished;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private(set) ?DateTimeImmutable $deletedAt = null;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(
        string $slug,
        string $title,
        string $excerpt,
        string $content,
        string $image,
        string $categoryId,
        string $authorName,
        int $readTime,
        bool $isPublished,
    ) {
        $this->slug = $slug;
        $this->title = $title;
        $this->excerpt = $excerpt;
        $this->content = $content;
        $this->image = $image;
        $this->categoryId = $categoryId;
        $this->authorName = $authorName;
        $this->readTime = $readTime;
        $this->isPublished = $isPublished;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        string $slug,
        string $title,
        string $excerpt,
        string $content,
        string $image,
        string $categoryId,
        string $authorName,
        int $readTime,
        bool $isPublished = false,
    ): self {
        return new self($slug, $title, $excerpt, $content, $image, $categoryId, $authorName, $readTime, $isPublished);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function edit(
        string $slug,
        string $title,
        string $excerpt,
        string $content,
        string $image,
        string $categoryId,
        string $authorName,
        int $readTime,
        bool $isPublished,
    ): void {
        $this->assertNotDeleted();
        $this->slug = $slug;
        $this->title = $title;
        $this->excerpt = $excerpt;
        $this->content = $content;
        $this->image = $image;
        $this->categoryId = $categoryId;
        $this->authorName = $authorName;
        $this->readTime = $readTime;
        $this->isPublished = $isPublished;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markDeleted(): void
    {
        $this->assertNotDeleted();
        $this->deletedAt = UtcClock::now();
        $this->isPublished = false;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    private function touch(): void
    {
        $this->updatedAt = UtcClock::now();
    }

    private function assertNotDeleted(): void
    {
        if ($this->deletedAt !== null) {
            throw new DomainExceptionModule(
                module: 'blog',
                message: 'error.blog_post_is_deleted',
                code: 3,
            );
        }
    }
}
