<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\Component;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'components')]
#[ORM\UniqueConstraint(name: 'uniq_components_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_components_is_indexable', columns: ['is_indexable'])]
#[ORM\Index(name: 'idx_components_sort_order', columns: ['sort_order'])]
class Component
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $slug;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $name;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private(set) ?array $synonyms;

    #[ORM\Column(name: 'short_description', type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $shortDescription;

    #[ORM\Column(name: 'seo_title', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $seoTitle;

    #[ORM\Column(name: 'seo_description', type: Types::STRING, length: 500, nullable: true)]
    private(set) ?string $seoDescription;

    #[ORM\Column(name: 'intro_text', type: Types::TEXT, nullable: true)]
    private(set) ?string $introText;

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
        string $slug,
        string $name,
        ?array $synonyms,
        ?string $shortDescription,
        ?string $seoTitle,
        ?string $seoDescription,
        ?string $introText,
        bool $isIndexable,
        int $sortOrder,
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->synonyms = $synonyms;
        $this->shortDescription = $shortDescription;
        $this->seoTitle = $seoTitle;
        $this->seoDescription = $seoDescription;
        $this->introText = $introText;
        $this->isIndexable = $isIndexable;
        $this->sortOrder = $sortOrder;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @param list<string>|null $synonyms
     * @throws DateMalformedStringException
     */
    public static function create(
        string $slug,
        string $name,
        ?array $synonyms = null,
        ?string $shortDescription = null,
        ?string $seoTitle = null,
        ?string $seoDescription = null,
        ?string $introText = null,
        bool $isIndexable = true,
        int $sortOrder = 0,
    ): self {
        return new self(
            slug: $slug,
            name: $name,
            synonyms: $synonyms,
            shortDescription: $shortDescription,
            seoTitle: $seoTitle,
            seoDescription: $seoDescription,
            introText: $introText,
            isIndexable: $isIndexable,
            sortOrder: $sortOrder,
        );
    }
}
