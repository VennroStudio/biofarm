<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductPurpose;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_purposes')]
#[ORM\UniqueConstraint(name: 'uniq_product_purposes_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_product_purposes_is_indexable', columns: ['is_indexable'])]
#[ORM\Index(name: 'idx_product_purposes_sort_order', columns: ['sort_order'])]
class ProductPurpose
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $slug;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $name;

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
        ?string $h1,
        ?string $seoTitle,
        ?string $seoDescription,
        ?string $introText,
        ?string $bottomText,
        bool $isIndexable,
        int $sortOrder,
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->h1 = $h1;
        $this->seoTitle = $seoTitle;
        $this->seoDescription = $seoDescription;
        $this->introText = $introText;
        $this->bottomText = $bottomText;
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
        ?string $h1 = null,
        ?string $seoTitle = null,
        ?string $seoDescription = null,
        ?string $introText = null,
        ?string $bottomText = null,
        bool $isIndexable = true,
        int $sortOrder = 0,
    ): self {
        return new self(
            slug: $slug,
            name: $name,
            h1: $h1,
            seoTitle: $seoTitle,
            seoDescription: $seoDescription,
            introText: $introText,
            bottomText: $bottomText,
            isIndexable: $isIndexable,
            sortOrder: $sortOrder,
        );
    }
}
