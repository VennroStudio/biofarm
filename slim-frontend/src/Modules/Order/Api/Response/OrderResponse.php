<?php

declare(strict_types=1);

namespace App\Modules\Order\Api\Response;

use App\Components\Api\ApiPayload;
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
        /** @var list<array{productId?: int, quantity?: int}> $products */
        $products = ApiPayload::optionalArray($item, 'products');

        return new self(
            id: ApiPayload::requireInt($item, 'id'),
            userId: ApiPayload::requireInt($item, 'userId'),
            products: ApiResponse::fromArrayList($products, OrderProductResponse::fromArray(...)),
            totalAmount: ApiPayload::requireFloat($item, 'totalAmount'),
            status: ApiPayload::requireString($item, 'status'),
            orderDate: ApiPayload::requireString($item, 'orderDate'),
            deliveryDate: ApiPayload::requireString($item, 'deliveryDate'),
        );
    }
}
