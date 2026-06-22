<?php

declare(strict_types=1);

namespace App\Modules\Product\Api;

use App\Components\Api\ApiClient;
use App\Components\Api\ApiPayload;
use App\Components\Api\ApiResponse;
use App\Modules\Product\Api\Response\ProductDeleteResponse;
use App\Modules\Product\Api\Response\ProductResponse;

final readonly class ProductApi
{
    public function __construct(
        private ApiClient $apiClient,
    ) {}

    /**
     * @return list<ProductResponse>
     */
    public function getProducts(int $page = 1, int $limit = 10): array
    {
        $payload = $this->apiClient->get('/products', [
            'page'  => $page,
            'limit' => $limit,
        ]);

        /** @var list<array{
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
         * }> $items */
        $items = ApiPayload::extractDataList($payload);

        return ApiResponse::fromArrayList($items, ProductResponse::fromArray(...));
    }

    public function getProduct(int $id): ProductResponse
    {
        /** @var array{
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
         * } $item */
        $item = $this->apiClient->get('/products/' . $id);

        return ProductResponse::fromArray($item);
    }

    /**
     * @return list<string>
     */
    public function getCategories(): array
    {
        return ApiPayload::extractStringList($this->apiClient->get('/products/categories'));
    }

    /**
     * @return list<ProductResponse>
     */
    public function getProductsByCategory(string $category, int $page = 1, int $limit = 10): array
    {
        $payload = $this->apiClient->get('/products/category/' . rawurlencode($category), [
            'page'  => $page,
            'limit' => $limit,
        ]);

        /** @var list<array{
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
         * }> $items */
        $items = ApiPayload::extractDataList($payload);

        return ApiResponse::fromArrayList($items, ProductResponse::fromArray(...));
    }

    /**
     * @param array<string, bool|float|int|string> $specs
     */
    public function createProduct(
        string $title,
        float $price,
        string $description,
        string $category,
        string $brand,
        int $stock,
        string $image,
        array $specs = [],
    ): ProductResponse {
        /** @var array{
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
         * } $item */
        $item = ApiPayload::extractData($this->apiClient->post('/products/create', [
            'title'       => $title,
            'price'       => $price,
            'description' => $description,
            'category'    => $category,
            'brand'       => $brand,
            'stock'       => $stock,
            'image'       => $image,
            'specs'       => $specs,
        ]));

        return ProductResponse::fromArray($item);
    }

    /**
     * @param array<string, bool|float|int|string>|null $specs
     */
    public function updateProduct(
        int $id,
        ?string $title = null,
        ?float $price = null,
        ?string $description = null,
        ?string $category = null,
        ?string $brand = null,
        ?int $stock = null,
        ?string $image = null,
        ?array $specs = null,
    ): ProductResponse {
        $payload = [
            'id' => $id,
        ];

        if ($title !== null) {
            $payload['title'] = $title;
        }

        if ($price !== null) {
            $payload['price'] = $price;
        }

        if ($description !== null) {
            $payload['description'] = $description;
        }

        if ($category !== null) {
            $payload['category'] = $category;
        }

        if ($brand !== null) {
            $payload['brand'] = $brand;
        }

        if ($stock !== null) {
            $payload['stock'] = $stock;
        }

        if ($image !== null) {
            $payload['image'] = $image;
        }

        if ($specs !== null) {
            $payload['specs'] = $specs;
        }

        /** @var array{
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
         * } $item */
        $item = ApiPayload::extractData($this->apiClient->patch('/products/update', $payload));

        return ProductResponse::fromArray($item);
    }

    public function deleteProduct(int $id): ProductDeleteResponse
    {
        /** @var array{id?: int, deleted?: bool, message?: string} $item */
        $item = ApiPayload::extractData($this->apiClient->delete('/products/delete', [
            'id' => $id,
        ]));

        return ProductDeleteResponse::fromArray($item + ['id' => $id]);
    }
}
