<?php

declare(strict_types=1);

namespace App\Modules\Product\Api;

use App\Components\Api\ApiClient;
use App\Components\Api\ApiPayload;
use App\Components\Api\ApiResponse;
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
}
