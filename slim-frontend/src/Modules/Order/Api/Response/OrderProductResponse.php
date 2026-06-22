<?php

declare(strict_types=1);

namespace App\Modules\Order\Api\Response;

final readonly class OrderProductResponse
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}

    /**
     * @param array{productId?: int, quantity?: int} $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            productId: $item['productId'] ?? 0,
            quantity: $item['quantity'] ?? 0,
        );
    }
}
