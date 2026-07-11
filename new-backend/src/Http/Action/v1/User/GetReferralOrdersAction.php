<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataItemsResponse;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetReferralOrdersAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $refs = [(string)$identity->id, $this->referralCode($identity->id)];

        $rows = $this->connection->createQueryBuilder()
            ->select(
                'id',
                'user_id',
                'status',
                'payment_status',
                'total',
                'bonus_used',
                'bonus_earned',
                'shipping_address',
                'payment_method',
                'tracking_number',
                'referred_by',
                'created_at',
                'paid_at',
                'updated_at',
            )
            ->from('orders')
            ->where('referred_by IN (:refs)')
            ->setParameter('refs', $refs, ArrayParameterType::STRING)
            ->orderBy('created_at', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        $items = $this->itemsByOrderId(array_map(static fn (array $row): string => (string)$row['id'], $rows));
        $orders = array_map(static fn (array $row): array => self::mapOrder($row, $items[(string)$row['id']] ?? []), $rows);

        return new JsonDataItemsResponse(count: \count($orders), items: $orders);
    }

    /**
     * @throws Exception
     */
    private function referralCode(int $userId): string
    {
        $code = $this->connection->fetchOne(
            'SELECT referral_code FROM user_profiles WHERE user_id = :userId',
            ['userId' => $userId],
        );

        return \is_string($code) && $code !== '' ? $code : 'bf-' . $userId;
    }

    /**
     * @param list<string> $orderIds
     * @return array<string, list<array<string, int|string>>>
     * @throws Exception
     */
    private function itemsByOrderId(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        $rows = $this->connection->createQueryBuilder()
            ->select('order_id', 'product_id', 'product_name', 'price', 'quantity')
            ->from('order_items')
            ->where('order_id IN (:orderIds)')
            ->setParameter('orderIds', $orderIds, ArrayParameterType::STRING)
            ->orderBy('id', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $items = [];
        foreach ($rows as $row) {
            $items[(string)$row['order_id']][] = [
                'product_id'   => (int)$row['product_id'],
                'product_name' => (string)$row['product_name'],
                'price'        => (int)$row['price'],
                'quantity'     => (int)$row['quantity'],
            ];
        }

        return $items;
    }

    /**
     * @param list<array<string, int|string>> $items
     * @return array<string, mixed>
     */
    private static function mapOrder(array $row, array $items): array
    {
        return [
            'id'               => (string)$row['id'],
            'user_id'          => (int)$row['user_id'],
            'status'           => (string)$row['status'],
            'payment_status'   => (string)$row['payment_status'],
            'total'            => (int)$row['total'],
            'bonus_used'       => (int)$row['bonus_used'],
            'bonus_earned'     => (int)$row['bonus_earned'],
            'shipping_address' => self::jsonObject($row['shipping_address'] ?? null),
            'payment_method'   => (string)$row['payment_method'],
            'tracking_number'  => $row['tracking_number'],
            'referred_by'      => $row['referred_by'],
            'created_at'       => (string)$row['created_at'],
            'paid_at'          => $row['paid_at'],
            'updated_at'       => $row['updated_at'],
            'items'            => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function jsonObject(mixed $value): array
    {
        if (\is_array($value)) {
            return $value;
        }

        if (\is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return \is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
