<?php

declare(strict_types=1);

namespace App\Modules\Product\Api;

use App\Components\Api\ApiClient;
use App\Components\Api\ApiException;
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
        $payload = [
            'title'       => $title,
            'price'       => $price,
            'description' => $description,
            'category'    => $category,
            'brand'       => $brand,
            'stock'       => $stock,
            'image'       => $image,
            'specs'       => $specs,
        ];

        try {
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
            $item = ApiPayload::extractData($this->apiClient->post('/products/create', $payload));
        } catch (ApiException) {
            $item = $this->demoProductPayload(
                id: 999,
                title: $title,
                price: $price,
                description: $description,
                category: $category,
                brand: $brand,
                stock: $stock,
                image: $image,
                specs: $specs,
            );
        }

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
        /** @var array{
         *     id: int,
         *     title?: string,
         *     price?: float,
         *     description?: string,
         *     category?: string,
         *     brand?: string,
         *     stock?: int,
         *     image?: string,
         *     specs?: array<string, bool|float|int|string>
         * } $payload */
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

        try {
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
        } catch (ApiException) {
            $item = $this->demoUpdatedProductPayload($id, $payload);
        }

        return ProductResponse::fromArray($item);
    }

    public function deleteProduct(int $id): ProductDeleteResponse
    {
        try {
            /** @var array{id?: int, deleted?: bool, message?: string} $item */
            $item = ApiPayload::extractData($this->apiClient->delete('/products/delete', [
                'id' => $id,
            ]));
        } catch (ApiException) {
            $item = [
                'id'      => $id,
                'deleted' => true,
                'message' => 'Demo delete accepted locally because the external API is read-only.',
            ];
        }

        return ProductDeleteResponse::fromArray([
            'id'      => $item['id'] ?? $id,
            'deleted' => $item['deleted'] ?? true,
            'message' => $item['message'] ?? 'Product deleted',
        ]);
    }

    /**
     * @param array<string, bool|float|int|string> $specs
     * @return array{
     *     id: int,
     *     title: string,
     *     price: float,
     *     description: string,
     *     category: string,
     *     brand: string,
     *     stock: int,
     *     image: string,
     *     specs: array<string, bool|float|int|string>,
     *     rating: array{rate: float, count: int}
     * }
     */
    private function demoProductPayload(
        int $id,
        string $title,
        float $price,
        string $description,
        string $category,
        string $brand,
        int $stock,
        string $image,
        array $specs = [],
    ): array {
        return [
            'id'          => $id,
            'title'       => $title,
            'price'       => $price,
            'description' => $description,
            'category'    => $category,
            'brand'       => $brand,
            'stock'       => $stock,
            'image'       => $image,
            'specs'       => $specs,
            'rating'      => [
                'rate'  => 0.0,
                'count' => 0,
            ],
        ];
    }

    /**
     * @param array{
     *     id: int,
     *     title?: string,
     *     price?: float,
     *     description?: string,
     *     category?: string,
     *     brand?: string,
     *     stock?: int,
     *     image?: string,
     *     specs?: array<string, bool|float|int|string>
     * } $payload
     * @return array{
     *     id: int,
     *     title: string,
     *     price: float,
     *     description: string,
     *     category: string,
     *     brand: string,
     *     stock: int,
     *     image: string,
     *     specs: array<string, bool|float|int|string>,
     *     rating: array{rate: float, count: int}
     * }
     */
    private function demoUpdatedProductPayload(int $id, array $payload): array
    {
        try {
            $current = $this->getProduct($id);
        } catch (ApiException) {
            return $this->demoProductPayload(
                id: $id,
                title: $payload['title'] ?? 'Updated demo product',
                price: $payload['price'] ?? 0.0,
                description: $payload['description'] ?? 'Demo update response',
                category: $payload['category'] ?? 'demo',
                brand: $payload['brand'] ?? 'Biofarm',
                stock: $payload['stock'] ?? 0,
                image: $payload['image'] ?? 'https://fakeapi.net/images/products/smartphone.jpg',
                specs: $payload['specs'] ?? [],
            );
        }

        return $this->demoProductPayload(
            id: $id,
            title: $payload['title'] ?? $current->title,
            price: $payload['price'] ?? $current->price,
            description: $payload['description'] ?? $current->description,
            category: $payload['category'] ?? $current->category,
            brand: $payload['brand'] ?? $current->brand,
            stock: $payload['stock'] ?? $current->stock,
            image: $payload['image'] ?? $current->image,
            specs: $payload['specs'] ?? $current->specs,
        );
    }
}
