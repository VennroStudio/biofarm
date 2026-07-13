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
     * @param list<array{id: int, path: string, alt: string|null, title: string|null, sort_order: int, is_main: bool, width: int|null, height: int|null}>|null $productImages
     * @param list<int>|null $attributeValueIds
     * @param list<int>|null $componentIds
     * @param list<int>|null $purposeIds
     * @param list<string>|null $features
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public ?string $h1,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public string $categoryId,
        public int $price,
        public ?int $oldPrice,
        public string $image,
        public ?string $imageAlt,
        public ?array $images,
        public ?array $productImages,
        public ?string $badge,
        public string $weight,
        public ?string $sku,
        public ?string $gtin,
        public string $availability,
        public string $description,
        public ?string $shortDescription,
        public ?string $ingredients,
        public ?array $attributeValueIds,
        public ?array $componentIds,
        public ?array $purposeIds,
        public ?int $productGroupId,
        public ?array $features,
        public ?string $wbLink,
        public ?string $ozonLink,
        public bool $isActive,
        public string $createdAt,
        public ?string $publishedAt,
        public ?string $updatedAt,
    ) {}

    public static function fields(): array
    {
        return [
            'id'                => 'id',
            'slug'              => 'slug',
            'name'              => 'name',
            'h1'                => 'h1',
            'seo_title'         => 'seo_title',
            'seo_description'   => 'seo_description',
            'category_id'       => 'category_id',
            'price'             => 'price',
            'old_price'         => 'old_price',
            'image'             => 'image',
            'image_alt'         => 'image_alt',
            'images'            => 'images',
            'product_images'    => "(SELECT JSON_ARRAYAGG(JSON_OBJECT(
                'id', pi.id,
                'path', pi.path,
                'alt', pi.alt,
                'title', pi.title,
                'sort_order', pi.sort_order,
                'is_main', pi.is_main,
                'width', pi.width,
                'height', pi.height
            )) FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.sort_order ASC, pi.id ASC)",
            'badge'             => 'badge',
            'weight'            => 'weight',
            'sku'               => 'sku',
            'gtin'              => 'gtin',
            'availability'      => 'availability',
            'description'       => 'description',
            'short_description' => 'short_description',
            'ingredients'       => 'ingredients',
            'attribute_value_ids' => '(SELECT JSON_ARRAYAGG(pav.attribute_value_id) FROM product_attribute_values pav WHERE pav.product_id = p.id)',
            'component_ids'     => '(SELECT JSON_ARRAYAGG(pc.component_id) FROM product_components pc WHERE pc.product_id = p.id)',
            'purpose_ids'       => '(SELECT JSON_ARRAYAGG(ppr.purpose_id) FROM product_purpose_relations ppr WHERE ppr.product_id = p.id)',
            'product_group_id' => '(SELECT pgi.group_id FROM product_group_items pgi INNER JOIN product_groups pg ON pg.id = pgi.group_id AND pg.deleted_at IS NULL WHERE pgi.product_id = p.id LIMIT 1)',
            'features'          => 'features',
            'wb_link'           => 'wb_link',
            'ozon_link'         => 'ozon_link',
            'is_active'         => 'is_active',
            'created_at'        => 'created_at',
            'published_at'      => 'published_at',
            'updated_at'        => 'updated_at',
        ];
    }

    /**
     * @param array{
     *     id: int,
     *     slug: string,
     *     name: string,
     *     h1: string|null,
     *     seo_title: string|null,
     *     seo_description: string|null,
     *     category_id: string,
     *     price: int,
     *     old_price: int|null,
     *     image: string,
     *     image_alt: string|null,
     *     images: list<string>|string|null,
     *     product_images: list<array{id: int, path: string, alt: string|null, title: string|null, sort_order: int, is_main: bool|int|string, width: int|null, height: int|null}>|string|null,
     *     badge: string|null,
     *     weight: string,
     *     sku: string|null,
     *     gtin: string|null,
     *     availability: string|null,
     *     description: string,
     *     short_description: string|null,
     *     ingredients: string|null,
     *     attribute_value_ids: list<int>|string|null,
     *     component_ids: list<int>|string|null,
     *     purpose_ids: list<int>|string|null,
     *     product_group_id: int|string|null,
     *     features: list<string>|string|null,
     *     wb_link: string|null,
     *     ozon_link: string|null,
     *     is_active: bool|int|string,
     *     created_at: string,
     *     published_at: string|null,
     *     updated_at: string|null
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            slug: $row['slug'],
            name: $row['name'],
            h1: $row['h1'],
            seoTitle: $row['seo_title'],
            seoDescription: $row['seo_description'],
            categoryId: $row['category_id'],
            price: (int)$row['price'],
            oldPrice: $row['old_price'] !== null ? (int)$row['old_price'] : null,
            image: $row['image'],
            imageAlt: $row['image_alt'],
            images: self::jsonList($row['images']),
            productImages: self::jsonProductImages($row['product_images']),
            badge: $row['badge'],
            weight: $row['weight'],
            sku: $row['sku'],
            gtin: $row['gtin'],
            availability: $row['availability'] ?? 'in_stock',
            description: $row['description'],
            shortDescription: $row['short_description'],
            ingredients: $row['ingredients'],
            attributeValueIds: self::jsonIntList($row['attribute_value_ids']),
            componentIds: self::jsonIntList($row['component_ids']),
            purposeIds: self::jsonIntList($row['purpose_ids']),
            productGroupId: $row['product_group_id'] !== null ? (int)$row['product_group_id'] : null,
            features: self::jsonList($row['features']),
            wbLink: $row['wb_link'],
            ozonLink: $row['ozon_link'],
            isActive: (bool)(int)$row['is_active'],
            createdAt: $row['created_at'],
            publishedAt: $row['published_at'],
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
            'h1'                => $this->h1,
            'seo_title'         => $this->seoTitle,
            'seo_description'   => $this->seoDescription,
            'category_id'       => $this->categoryId,
            'price'             => $this->price,
            'old_price'         => $this->oldPrice,
            'image'             => $this->image,
            'image_alt'         => $this->imageAlt,
            'images'            => $this->images,
            'product_images'    => $this->productImages,
            'badge'             => $this->badge,
            'weight'            => $this->weight,
            'sku'               => $this->sku,
            'gtin'              => $this->gtin,
            'availability'      => $this->availability,
            'description'       => $this->description,
            'short_description' => $this->shortDescription,
            'ingredients'       => $this->ingredients,
            'attribute_value_ids' => $this->attributeValueIds,
            'component_ids'     => $this->componentIds,
            'purpose_ids'       => $this->purposeIds,
            'product_group_id' => $this->productGroupId,
            'features'          => $this->features,
            'wb_link'           => $this->wbLink,
            'ozon_link'         => $this->ozonLink,
            'is_active'         => $this->isActive,
            'created_at'        => $this->createdAt,
            'published_at'      => $this->publishedAt,
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

    /**
     * @return list<int>|null
     */
    private static function jsonIntList(array|string|null $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $items = \is_array($value) ? $value : json_decode($value, true);
        if (!\is_array($items)) {
            return null;
        }

        return array_values(array_filter(
            array_map(static fn (mixed $item): int => (int)$item, $items),
            static fn (int $item): bool => $item > 0,
        ));
    }

    /**
     * @return list<array{id: int, path: string, alt: string|null, title: string|null, sort_order: int, is_main: bool, width: int|null, height: int|null}>|null
     */
    private static function jsonProductImages(array|string|null $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $items = \is_array($value) ? $value : json_decode($value, true);
        if (!\is_array($items)) {
            return null;
        }

        $images = [];
        foreach ($items as $item) {
            if (!\is_array($item) || !isset($item['path']) || trim((string)$item['path']) === '') {
                continue;
            }

            $images[] = [
                'id' => (int)($item['id'] ?? 0),
                'path' => trim((string)$item['path']),
                'alt' => isset($item['alt']) && trim((string)$item['alt']) !== '' ? trim((string)$item['alt']) : null,
                'title' => isset($item['title']) && trim((string)$item['title']) !== '' ? trim((string)$item['title']) : null,
                'sort_order' => (int)($item['sort_order'] ?? 0),
                'is_main' => (bool)(int)($item['is_main'] ?? 0),
                'width' => isset($item['width']) ? (int)$item['width'] : null,
                'height' => isset($item['height']) ? (int)$item['height'] : null,
            ];
        }

        usort($images, static fn (array $left, array $right): int => $left['sort_order'] <=> $right['sort_order']);

        return $images === [] ? null : $images;
    }
}
