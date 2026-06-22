<?php

declare(strict_types=1);

namespace App\Modules\Product\Api\Response;

use App\Components\Api\ApiPayload;

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
        $rating = ApiPayload::optionalArray($item, 'rating');

        return new self(
            id: ApiPayload::requireInt($item, 'id'),
            title: ApiPayload::requireString($item, 'title'),
            price: ApiPayload::requireFloat($item, 'price'),
            description: ApiPayload::requireString($item, 'description'),
            category: ApiPayload::requireString($item, 'category'),
            brand: ApiPayload::requireString($item, 'brand'),
            stock: ApiPayload::requireInt($item, 'stock'),
            image: ApiPayload::requireString($item, 'image'),
            specs: ApiPayload::optionalScalarMap($item, 'specs'),
            ratingRate: ApiPayload::optionalFloat($rating, 'rate'),
            ratingCount: ApiPayload::optionalInt($rating, 'count'),
        );
    }
}
