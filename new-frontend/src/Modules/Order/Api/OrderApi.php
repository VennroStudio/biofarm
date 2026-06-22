<?php

declare(strict_types=1);

namespace App\Modules\Order\Api;

use App\Components\Api\ApiClient;
use App\Components\Api\ApiPayload;
use App\Components\Api\ApiResponse;
use App\Modules\Order\Api\Response\OrderResponse;

final readonly class OrderApi
{
    public function __construct(
        private ApiClient $apiClient,
    ) {}

    /**
     * @return list<OrderResponse>
     */
    public function getOrders(int $page = 1, int $limit = 10, ?string $status = null, ?int $userId = null): array
    {
        $query = [
            'page'  => $page,
            'limit' => $limit,
        ];

        if ($status !== null) {
            $query['status'] = $status;
        }

        if ($userId !== null) {
            $query['userId'] = $userId;
        }

        $payload = $this->apiClient->get('/orders', $query);

        /** @var list<array{
         *     id?: int,
         *     userId?: int,
         *     products?: list<array{productId?: int, quantity?: int}>,
         *     totalAmount?: float|int,
         *     status?: string,
         *     orderDate?: string,
         *     deliveryDate?: string
         * }> $items */
        $items = ApiPayload::extractDataList($payload);

        return ApiResponse::fromArrayList($items, OrderResponse::fromArray(...));
    }

    public function getOrder(int $id): OrderResponse
    {
        /** @var array{
         *     id?: int,
         *     userId?: int,
         *     products?: list<array{productId?: int, quantity?: int}>,
         *     totalAmount?: float|int,
         *     status?: string,
         *     orderDate?: string,
         *     deliveryDate?: string
         * } $item */
        $item = $this->apiClient->get('/orders/' . $id);

        return OrderResponse::fromArray($item);
    }

    /**
     * @return list<OrderResponse>
     */
    public function getUserOrders(int $userId, int $page = 1, int $limit = 10): array
    {
        $payload = $this->apiClient->get('/users/' . $userId . '/orders', [
            'page'  => $page,
            'limit' => $limit,
        ]);

        /** @var list<array{
         *     id?: int,
         *     userId?: int,
         *     products?: list<array{productId?: int, quantity?: int}>,
         *     totalAmount?: float|int,
         *     status?: string,
         *     orderDate?: string,
         *     deliveryDate?: string
         * }> $items */
        $items = ApiPayload::extractDataList($payload);

        return ApiResponse::fromArrayList($items, OrderResponse::fromArray(...));
    }
}
