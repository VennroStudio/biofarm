<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductCategory;

use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'categories')]
#[ORM\UniqueConstraint(name: 'uniq_categories_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_categories_parent_id', columns: ['parent_id'])]
#[ORM\Index(name: 'idx_categories_is_indexable', columns: ['is_indexable'])]
#[ORM\Index(name: 'idx_categories_sort_order', columns: ['sort_order'])]
class ProductCategory
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $slug;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $name;

    #[ORM\Column(name: 'parent_id', type: Types::INTEGER, nullable: true)]
    private(set) ?int $parentId;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $h1;

    #[ORM\Column(name: 'seo_title', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $seoTitle;

    #[ORM\Column(name: 'seo_description', type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $seoDescription;

    #[ORM\Column(name: 'intro_text', type: Types::TEXT, nullable: true)]
    private(set) ?string $introText;

    #[ORM\Column(name: 'bottom_text', type: Types::TEXT, nullable: true)]
    private(set) ?string $bottomText;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $image;

    #[ORM\Column(name: 'is_indexable', type: Types::BOOLEAN, options: ['default' => true])]
    private(set) bool $isIndexable;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0])]
    private(set) int $sortOrder;

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
        string $name,
        ?int $parentId,
        ?string $h1,
        ?string $seoTitle,
        ?string $seoDescription,
        ?string $introText,
        ?string $bottomText,
        ?string $image,
        bool $isIndexable,
        int $sortOrder,
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->parentId = $parentId;
        $this->h1 = $h1;
        $this->seoTitle = $seoTitle;
        $this->seoDescription = $seoDescription;
        $this->introText = $introText;
        $this->bottomText = $bottomText;
        $this->image = $image;
        $this->isIndexable = $isIndexable;
        $this->sortOrder = $sortOrder;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        string $slug,
        string $name,
        ?int $parentId = null,
        ?string $h1 = null,
        ?string $seoTitle = null,
        ?string $seoDescription = null,
        ?string $introText = null,
        ?string $bottomText = null,
        ?string $image = null,
        bool $isIndexable = true,
        int $sortOrder = 0,
    ): self
    {
        return new self(
            slug: $slug,
            name: $name,
            parentId: $parentId,
            h1: $h1,
            seoTitle: $seoTitle,
            seoDescription: $seoDescription,
            introText: $introText,
            bottomText: $bottomText,
            image: $image,
            isIndexable: $isIndexable,
            sortOrder: $sortOrder,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function edit(
        string $slug,
        string $name,
        ?int $parentId = null,
        ?string $h1 = null,
        ?string $seoTitle = null,
        ?string $seoDescription = null,
        ?string $introText = null,
        ?string $bottomText = null,
        ?string $image = null,
        bool $isIndexable = true,
        int $sortOrder = 0,
    ): void
    {
        $this->assertNotDeleted();
        $this->slug = $slug;
        $this->name = $name;
        $this->parentId = $parentId;
        $this->h1 = $h1;
        $this->seoTitle = $seoTitle;
        $this->seoDescription = $seoDescription;
        $this->introText = $introText;
        $this->bottomText = $bottomText;
        $this->image = $image;
        $this->isIndexable = $isIndexable;
        $this->sortOrder = $sortOrder;
        $this->touch();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markDeleted(): void
    {
        $this->assertNotDeleted();
        $this->deletedAt = UtcClock::now();
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
                module: 'product',
                message: 'error.category_is_deleted',
                code: 3,
            );
        }
    }
}
