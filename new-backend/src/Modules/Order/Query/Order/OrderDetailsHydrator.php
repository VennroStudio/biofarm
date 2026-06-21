<?php

declare(strict_types=1);

namespace App\Modules\Order\Query\Order;

use App\Components\ReadModel\ReadModelFields;
use App\Modules\Order\ReadModel\Order\OrderDetails;
use App\Modules\Order\ReadModel\OrderItem\OrderItemDetails;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class OrderDetailsHydrator
{
    private const string ORDER_ITEMS_TABLE = 'order_items';

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @param list<array{
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
     * }> $rows
     * @return list<OrderDetails>
     * @throws Exception
     */
    public function hydrate(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $itemsByOrderId = $this->itemsByOrderId(array_values(array_map(
            static fn(array $row): string => $row['id'],
            $rows,
        )));

        return array_map(
            static fn(array $row): OrderDetails => OrderDetails::fromRowWithItems(
                $row,
                $itemsByOrderId[$row['id']] ?? [],
            ),
            $rows,
        );
    }

    /**
     * @param list<string> $orderIds
     * @return array<string, list<OrderItemDetails>>
     * @throws Exception
     */
    private function itemsByOrderId(array $orderIds): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select(...ReadModelFields::select(OrderItemDetails::fields(), 'oi'))
            ->from(self::ORDER_ITEMS_TABLE, 'oi')
            ->where('oi.order_id IN (:orderIds)')
            ->setParameter('orderIds', $orderIds, ArrayParameterType::STRING)
            ->orderBy('oi.id', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $itemsByOrderId = [];
        foreach ($rows as $row) {
            /** @var array{id: int|string, order_id: string, product_id: int|string, product_name: string, price: int|string, quantity: int|string} $row */
            $item = OrderItemDetails::fromRow($row);
            $itemsByOrderId[$item->orderId][] = $item;
        }

        return $itemsByOrderId;
    }
}
