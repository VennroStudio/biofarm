<?php

declare(strict_types=1);

namespace App\Modules\Order\ReadModel\Order;

use App\Components\ReadModel\FromRowsTrait;
use App\Modules\Order\ReadModel\Order\Interface\OrderModelInterface;
use App\Modules\Order\ReadModel\OrderItem\OrderItemDetails;
use Override;

final readonly class OrderDetails implements OrderModelInterface
{
    use FromRowsTrait;

    /**
     * @param array{
     *     name?: string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     city?: string|null,
     *     address?: string|null,
     *     postal_code?: string|null,
     *     postalCode?: string|null
     * } $shippingAddress
     * @param list<OrderItemDetails> $items
     */
    public function __construct(
        public string $id,
        public int $userId,
        public string $status,
        public string $paymentStatus,
        public int $total,
        public int $bonusUsed,
        public int $bonusEarned,
        public array $shippingAddress,
        public string $paymentMethod,
        public ?string $trackingNumber,
        public ?string $referredBy,
        public string $createdAt,
        public ?string $paidAt,
        public ?string $updatedAt,
        public array $items = [],
    ) {}

    public static function fields(): array
    {
        return [
            'id'               => 'id',
            'user_id'          => 'user_id',
            'status'           => 'status',
            'payment_status'   => 'payment_status',
            'total'            => 'total',
            'bonus_used'       => 'bonus_used',
            'bonus_earned'     => 'bonus_earned',
            'shipping_address' => 'shipping_address',
            'payment_method'   => 'payment_method',
            'tracking_number'  => 'tracking_number',
            'referred_by'      => 'referred_by',
            'created_at'       => 'created_at',
            'paid_at'          => 'paid_at',
            'updated_at'       => 'updated_at',
        ];
    }

    /**
     * @param array{
     *     id: string,
     *     user_id: int|string,
     *     status: string,
     *     payment_status: string,
     *     total: int|string,
     *     bonus_used: int|string,
     *     bonus_earned: int|string,
     *     shipping_address: array{
     *         name?: string|null,
     *         phone?: string|null,
     *         email?: string|null,
     *         city?: string|null,
     *         address?: string|null,
     *         postal_code?: string|null,
     *         postalCode?: string|null
     *     }|string|null,
     *     payment_method: string,
     *     tracking_number: string|null,
     *     referred_by: string|null,
     *     created_at: string,
     *     paid_at: string|null,
     *     updated_at: string|null
     * } $row
     */
    public static function fromRow(array $row): self
    {
        return self::fromRowWithItems($row, []);
    }

    /**
     * @param array{
     *     id: string,
     *     user_id: int|string,
     *     status: string,
     *     payment_status: string,
     *     total: int|string,
     *     bonus_used: int|string,
     *     bonus_earned: int|string,
     *     shipping_address: array{
     *         name?: string|null,
     *         phone?: string|null,
     *         email?: string|null,
     *         city?: string|null,
     *         address?: string|null,
     *         postal_code?: string|null,
     *         postalCode?: string|null
     *     }|string|null,
     *     payment_method: string,
     *     tracking_number: string|null,
     *     referred_by: string|null,
     *     created_at: string,
     *     paid_at: string|null,
     *     updated_at: string|null
     * } $row
     * @param list<OrderItemDetails> $items
     */
    public static function fromRowWithItems(array $row, array $items): self
    {
        return new self(
            id: $row['id'],
            userId: (int)$row['user_id'],
            status: $row['status'],
            paymentStatus: $row['payment_status'],
            total: (int)$row['total'],
            bonusUsed: (int)$row['bonus_used'],
            bonusEarned: (int)$row['bonus_earned'],
            shippingAddress: self::jsonObject($row['shipping_address']),
            paymentMethod: $row['payment_method'],
            trackingNumber: $row['tracking_number'],
            referredBy: $row['referred_by'],
            createdAt: $row['created_at'],
            paidAt: $row['paid_at'],
            updatedAt: $row['updated_at'],
            items: $items,
        );
    }

    #[Override]
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array{
     *     id: string,
     *     user_id: int,
     *     status: string,
     *     payment_status: string,
     *     total: int,
     *     bonus_used: int,
     *     bonus_earned: int,
     *     shipping_address: array{
     *         name?: string|null,
     *         phone?: string|null,
     *         email?: string|null,
     *         city?: string|null,
     *         address?: string|null,
     *         postal_code?: string|null,
     *         postalCode?: string|null
     *     },
     *     payment_method: string,
     *     tracking_number: string|null,
     *     referred_by: string|null,
     *     created_at: string,
     *     paid_at: string|null,
     *     updated_at: string|null,
     *     items: list<array{product_id: int, product_name: string, price: int, quantity: int}>
     * }
     */
    #[Override]
    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'user_id'          => $this->userId,
            'status'           => $this->status,
            'payment_status'   => $this->paymentStatus,
            'total'            => $this->total,
            'bonus_used'       => $this->bonusUsed,
            'bonus_earned'     => $this->bonusEarned,
            'shipping_address' => $this->shippingAddress,
            'payment_method'   => $this->paymentMethod,
            'tracking_number'  => $this->trackingNumber,
            'referred_by'      => $this->referredBy,
            'created_at'       => $this->createdAt,
            'paid_at'          => $this->paidAt,
            'updated_at'       => $this->updatedAt,
            'items'            => array_map(
                static fn (OrderItemDetails $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }

    /**
     * @return array{
     *     name?: string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     city?: string|null,
     *     address?: string|null,
     *     postal_code?: string|null,
     *     postalCode?: string|null
     * }
     */
    private static function jsonObject(array|string|null $value): array
    {
        $address = \is_array($value) ? $value : [];

        if (\is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            $address = \is_array($decoded) ? $decoded : [];
        }

        $result = [];
        foreach (['name', 'phone', 'email', 'city', 'address', 'postal_code', 'postalCode'] as $key) {
            if (!\array_key_exists($key, $address)) {
                continue;
            }

            $field = $address[$key];
            if ($field === null || \is_scalar($field)) {
                $result[$key] = $field !== null ? (string)$field : null;
            }
        }

        return $result;
    }
}
