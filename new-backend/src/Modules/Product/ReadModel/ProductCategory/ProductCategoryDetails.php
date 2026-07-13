<?php

declare(strict_types=1);

namespace App\Modules\Product\ReadModel\ProductCategory;

use App\Components\ReadModel\FromRowsTrait;
use App\Modules\Product\ReadModel\ProductCategory\Interface\ProductCategoryModelInterface;
use Override;

final readonly class ProductCategoryDetails implements ProductCategoryModelInterface
{
    use FromRowsTrait;

    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public ?int $parentId,
        public ?string $h1,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?string $introText,
        public ?string $bottomText,
        public ?string $image,
        public bool $isIndexable,
        public int $sortOrder,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fields(): array
    {
        return [
            'id'         => 'id',
            'slug'       => 'slug',
            'name'       => 'name',
            'parent_id'  => 'parent_id',
            'h1'         => 'h1',
            'seo_title'  => 'seo_title',
            'seo_description' => 'seo_description',
            'intro_text' => 'intro_text',
            'bottom_text' => 'bottom_text',
            'image'      => 'image',
            'is_indexable' => 'is_indexable',
            'sort_order' => 'sort_order',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }

    /**
     * @param array{
     *     id: int,
     *     slug: string,
     *     name: string,
     *     parent_id: int|string|null,
     *     h1: string|null,
     *     seo_title: string|null,
     *     seo_description: string|null,
     *     intro_text: string|null,
     *     bottom_text: string|null,
     *     image: string|null,
     *     is_indexable: bool|int|string,
     *     sort_order: int|string,
     *     created_at: string,
     *     updated_at: string|null
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            slug: $row['slug'],
            name: $row['name'],
            parentId: $row['parent_id'] !== null ? (int)$row['parent_id'] : null,
            h1: $row['h1'],
            seoTitle: $row['seo_title'],
            seoDescription: $row['seo_description'],
            introText: $row['intro_text'],
            bottomText: $row['bottom_text'],
            image: $row['image'],
            isIndexable: (bool)(int)$row['is_indexable'],
            sortOrder: (int)$row['sort_order'],
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
            'id'         => $this->id,
            'slug'       => $this->slug,
            'name'       => $this->name,
            'parent_id'  => $this->parentId,
            'h1'         => $this->h1,
            'seo_title'  => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'intro_text' => $this->introText,
            'bottom_text' => $this->bottomText,
            'image'      => $this->image,
            'is_indexable' => $this->isIndexable,
            'sort_order' => $this->sortOrder,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
