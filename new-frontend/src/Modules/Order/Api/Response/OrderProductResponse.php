<?php

declare(strict_types=1);

namespace App\Modules\Order\Api\Response;

use App\Components\Api\ApiPayload;

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
            productId: ApiPayload::requireInt($item, 'productId'),
            quantity: ApiPayload::requireInt($item, 'quantity'),
        );
    }
}
