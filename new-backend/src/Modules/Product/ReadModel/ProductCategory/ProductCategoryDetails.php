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
        public string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fields(): array
    {
        return [
            'id'         => 'id',
            'slug'       => 'slug',
            'name'       => 'name',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }

    /**
     * @param array{
     *     id: int,
     *     slug: string,
     *     name: string,
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
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
