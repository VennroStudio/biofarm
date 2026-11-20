<?php

declare(strict_types=1);

namespace App\Modules\Order\ReadModel\OrderItem;

use App\Components\ReadModel\FromRowsTrait;

final readonly class OrderItemDetails
{
    use FromRowsTrait;

    public function __construct(
        public int $id,
        public string $orderId,
        public int $productId,
        public string $productName,
        public int $price,
        public int $quantity,
    ) {}

    public static function fields(): array
    {
        return [
            'id'           => 'id',
            'order_id'     => 'order_id',
            'product_id'   => 'product_id',
            'product_name' => 'product_name',
            'price'        => 'price',
            'quantity'     => 'quantity',
        ];
    }

    /**
     * @param array{id: int|string, order_id: string, product_id: int|string, product_name: string, price: int|string, quantity: int|string} $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int)$row['id'],
            orderId: $row['order_id'],
            productId: (int)$row['product_id'],
            productName: $row['product_name'],
            price: (int)$row['price'],
            quantity: (int)$row['quantity'],
        );
    }

    /**
     * @return array{product_id: int, product_name: string, price: int, quantity: int}
     */
    public function toArray(): array
    {
        return [
            'product_id'   => $this->productId,
            'product_name' => $this->productName,
            'price'        => $this->price,
            'quantity'     => $this->quantity,
        ];
    }
}
