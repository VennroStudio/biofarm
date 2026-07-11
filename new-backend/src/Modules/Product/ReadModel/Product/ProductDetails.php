<?php

declare(strict_types=1);

namespace App\Modules\Product\ReadModel\Product;

use App\Components\ReadModel\FromRowsTrait;
use App\Modules\Product\ReadModel\Product\Interface\ProductModelInterface;
use Override;

final readonly class ProductDetails implements ProductModelInterface
{
    use FromRowsTrait;

    /**
     * @param list<string>|null $images
     * @param list<string>|null $features
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public string $categoryId,
        public int $price,
        public ?int $oldPrice,
        public string $image,
        public ?array $images,
        public ?string $badge,
        public string $weight,
        public string $description,
        public ?string $shortDescription,
        public ?string $ingredients,
        public ?array $features,
        public ?string $wbLink,
        public ?string $ozonLink,
        public bool $isActive,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fields(): array
    {
        return [
            'id'                => 'id',
            'slug'              => 'slug',
            'name'              => 'name',
            'category_id'       => 'category_id',
            'price'             => 'price',
            'old_price'         => 'old_price',
            'image'             => 'image',
            'images'            => 'images',
            'badge'             => 'badge',
            'weight'            => 'weight',
            'description'       => 'description',
            'short_description' => 'short_description',
            'ingredients'       => 'ingredients',
            'features'          => 'features',
            'wb_link'           => 'wb_link',
            'ozon_link'         => 'ozon_link',
            'is_active'         => 'is_active',
            'created_at'        => 'created_at',
            'updated_at'        => 'updated_at',
        ];
    }

    /**
     * @param array{
     *     id: int,
     *     slug: string,
     *     name: string,
     *     category_id: string,
     *     price: int,
     *     old_price: int|null,
     *     image: string,
     *     images: list<string>|string|null,
     *     badge: string|null,
     *     weight: string,
     *     description: string,
     *     short_description: string|null,
     *     ingredients: string|null,
     *     features: list<string>|string|null,
     *     wb_link: string|null,
     *     ozon_link: string|null,
     *     is_active: bool|int|string,
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
            categoryId: $row['category_id'],
            price: (int)$row['price'],
            oldPrice: $row['old_price'] !== null ? (int)$row['old_price'] : null,
            image: $row['image'],
            images: self::jsonList($row['images']),
            badge: $row['badge'],
            weight: $row['weight'],
            description: $row['description'],
            shortDescription: $row['short_description'],
            ingredients: $row['ingredients'],
            features: self::jsonList($row['features']),
            wbLink: $row['wb_link'],
            ozonLink: $row['ozon_link'],
            isActive: (bool)(int)$row['is_active'],
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
            'id'                => $this->id,
            'slug'              => $this->slug,
            'name'              => $this->name,
            'category_id'       => $this->categoryId,
            'price'             => $this->price,
            'old_price'         => $this->oldPrice,
            'image'             => $this->image,
            'images'            => $this->images,
            'badge'             => $this->badge,
            'weight'            => $this->weight,
            'description'       => $this->description,
            'short_description' => $this->shortDescription,
            'ingredients'       => $this->ingredients,
            'features'          => $this->features,
            'wb_link'           => $this->wbLink,
            'ozon_link'         => $this->ozonLink,
            'is_active'         => $this->isActive,
            'created_at'        => $this->createdAt,
            'updated_at'        => $this->updatedAt,
        ];
    }

    /**
     * @return list<string>|null
     */
    private static function jsonList(array|string|null $value): ?array
    {
        if ($value === null || \is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return \is_array($decoded) ? $decoded : null;
    }
}
