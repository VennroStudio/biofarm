<?php

declare(strict_types=1);

namespace App\Modules\Entity\OrderItem;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: OrderItem::DB_NAME)]
#[ORM\Index(fields: ['orderId'], name: 'IDX_ORDER')]
final class OrderItem
{
    public const DB_NAME = 'biofarm_order_items';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private null|int|string $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $orderId;

    #[ORM\Column(type: 'bigint')]
    private int $productId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $productName;

    #[ORM\Column(type: 'integer')]
    private int $price;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    private function __construct(
        string $orderId,
        int $productId,
        string $productName,
        int $price,
        int $quantity,
    ) {
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->productName = $productName;
        $this->price = $price;
        $this->quantity = $quantity;
    }

    public static function create(
        string $orderId,
        int $productId,
        string $productName,
        int $price,
        int $quantity,
    ): self {
        return new self(
            orderId: $orderId,
            productId: $productId,
            productName: $productName,
            price: $price,
            quantity: $quantity,
        );
    }

    public function getId(): int
    {
        if (null === $this->id) {
            throw new DomainException('Id not set');
        }
        return (int)$this->id;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getTotal(): int
    {
        return $this->price * $this->quantity;
    }
}
