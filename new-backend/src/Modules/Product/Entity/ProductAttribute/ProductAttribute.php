<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductAttribute;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'attributes')]
#[ORM\UniqueConstraint(name: 'uniq_attributes_slug', columns: ['slug'])]
#[ORM\UniqueConstraint(name: 'uniq_attributes_filter_prefix', columns: ['filter_prefix'])]
#[ORM\Index(name: 'idx_attributes_filterable', columns: ['is_filterable'])]
#[ORM\Index(name: 'idx_attributes_sort_order', columns: ['sort_order'])]
class ProductAttribute
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $slug;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $name;

    #[ORM\Column(name: 'filter_prefix', type: Types::STRING, length: 100, nullable: true)]
    private(set) ?string $filterPrefix;

    #[ORM\Column(name: 'is_filterable', type: Types::BOOLEAN, options: ['default' => true])]
    private(set) bool $isFilterable;

    #[ORM\Column(name: 'is_indexable', type: Types::BOOLEAN, options: ['default' => true])]
    private(set) bool $isIndexable;

    #[ORM\Column(name: 'show_on_product', type: Types::BOOLEAN, options: ['default' => true])]
    private(set) bool $showOnProduct;

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
        ?string $filterPrefix,
        bool $isFilterable,
        bool $isIndexable,
        bool $showOnProduct,
        int $sortOrder,
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->filterPrefix = $filterPrefix;
        $this->isFilterable = $isFilterable;
        $this->isIndexable = $isIndexable;
        $this->showOnProduct = $showOnProduct;
        $this->sortOrder = $sortOrder;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        string $slug,
        string $name,
        ?string $filterPrefix = null,
        bool $isFilterable = true,
        bool $isIndexable = true,
        bool $showOnProduct = true,
        int $sortOrder = 0,
    ): self {
        return new self(
            slug: $slug,
            name: $name,
            filterPrefix: $filterPrefix,
            isFilterable: $isFilterable,
            isIndexable: $isIndexable,
            showOnProduct: $showOnProduct,
            sortOrder: $sortOrder,
        );
    }
}
