<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\AttributeValue;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'attribute_values')]
#[ORM\UniqueConstraint(name: 'uniq_attribute_values_attribute_slug', columns: ['attribute_id', 'slug'])]
#[ORM\Index(name: 'idx_attribute_values_attribute_id', columns: ['attribute_id'])]
#[ORM\Index(name: 'idx_attribute_values_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_attribute_values_sort_order', columns: ['sort_order'])]
class AttributeValue
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(name: 'attribute_id', type: Types::INTEGER)]
    private(set) int $attributeId;

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

    #[ORM\Column(name: 'short_description', type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $shortDescription;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private(set) ?array $synonyms;

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
     * @param list<string>|null $synonyms
     * @throws DateMalformedStringException
     */
    private function __construct(
        int $attributeId,
        string $slug,
        string $name,
        ?string $h1,
        ?string $seoTitle,
        ?string $seoDescription,
        ?string $introText,
        ?string $bottomText,
        ?string $shortDescription,
        ?array $synonyms,
        bool $isIndexable,
        int $sortOrder,
    ) {
        $this->attributeId = $attributeId;
        $this->slug = $slug;
        $this->name = $name;
        $this->h1 = $h1;
        $this->seoTitle = $seoTitle;
        $this->seoDescription = $seoDescription;
        $this->introText = $introText;
        $this->bottomText = $bottomText;
        $this->shortDescription = $shortDescription;
        $this->synonyms = $synonyms;
        $this->isIndexable = $isIndexable;
        $this->sortOrder = $sortOrder;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @param list<string>|null $synonyms
     * @throws DateMalformedStringException
     */
    public static function create(
        int $attributeId,
        string $slug,
        string $name,
        ?string $h1 = null,
        ?string $seoTitle = null,
        ?string $seoDescription = null,
        ?string $introText = null,
        ?string $bottomText = null,
        ?string $shortDescription = null,
        ?array $synonyms = null,
        bool $isIndexable = true,
        int $sortOrder = 0,
    ): self {
        return new self(
            attributeId: $attributeId,
            slug: $slug,
            name: $name,
            h1: $h1,
            seoTitle: $seoTitle,
            seoDescription: $seoDescription,
            introText: $introText,
            bottomText: $bottomText,
            shortDescription: $shortDescription,
            synonyms: $synonyms,
            isIndexable: $isIndexable,
            sortOrder: $sortOrder,
        );
    }
}
