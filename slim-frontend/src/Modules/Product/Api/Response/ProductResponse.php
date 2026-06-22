<?php

declare(strict_types=1);

namespace App\Modules\Product\Api\Response;

final readonly class ProductResponse
{
    /**
     * @param array<string, bool|float|int|string> $specs
     */
    public function __construct(
        public int $id,
        public string $title,
        public float $price,
        public string $description,
        public string $category,
        public string $brand,
        public int $stock,
        public string $image,
        public array $specs,
        public float $ratingRate,
        public int $ratingCount,
    ) {}

    /**
     * @param array{
     *     id?: int,
     *     title?: string,
     *     price?: float|int,
     *     description?: string,
     *     category?: string,
     *     brand?: string,
     *     stock?: int,
     *     image?: string,
     *     specs?: array<string, bool|float|int|string>|null,
     *     rating?: array{rate?: float|int, count?: int}
     * } $item
     */
    public static function fromArray(array $item): self
    {
        $rating = $item['rating'] ?? [];

        return new self(
            id: $item['id'] ?? 0,
            title: $item['title'] ?? '',
            price: (float)($item['price'] ?? 0),
            description: $item['description'] ?? '',
            category: $item['category'] ?? 'uncategorized',
            brand: $item['brand'] ?? 'Unknown',
            stock: $item['stock'] ?? 0,
            image: $item['image'] ?? '',
            specs: $item['specs'] ?? [],
            ratingRate: (float)($rating['rate'] ?? 0),
            ratingCount: $rating['count'] ?? 0,
        );
    }
}
