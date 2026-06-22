<?php

declare(strict_types=1);

namespace App\Modules\Order\Api\Response;

use App\Components\Api\ApiResponse;

final readonly class OrderResponse
{
    /**
     * @param list<OrderProductResponse> $products
     */
    public function __construct(
        public int $id,
        public int $userId,
        public array $products,
        public float $totalAmount,
        public string $status,
        public string $orderDate,
        public string $deliveryDate,
    ) {}

    /**
     * @param array{
     *     id?: int,
     *     userId?: int,
     *     products?: list<array{productId?: int, quantity?: int}>,
     *     totalAmount?: float|int,
     *     status?: string,
     *     orderDate?: string,
     *     deliveryDate?: string
     * } $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            id: $item['id'] ?? 0,
            userId: $item['userId'] ?? 0,
            products: ApiResponse::fromArrayList($item['products'] ?? [], OrderProductResponse::fromArray(...)),
            totalAmount: (float)($item['totalAmount'] ?? 0),
            status: $item['status'] ?? '',
            orderDate: $item['orderDate'] ?? '',
            deliveryDate: $item['deliveryDate'] ?? '',
        );
    }
}
