<?php

declare(strict_types=1);

namespace App\Modules\Order\Entity\OrderItem;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
#[ORM\Index(name: 'idx_order_items_order_id', columns: ['order_id'])]
class OrderItem
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private(set) string $orderId;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $productId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $productName;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $price;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $quantity;

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
        return new self($orderId, $productId, $productName, $price, $quantity);
    }

    public function getTotal(): int
    {
        return $this->price * $this->quantity;
    }
}
