<?php

declare(strict_types=1);

namespace App\Modules\Order\Query\Order\GetById;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\Order\Query\Order\OrderDetailsHydrator;
use App\Modules\Order\ReadModel\Order\OrderDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class OrderGetByIdFetcher
{
    private const string TABLE = 'orders';
    private const int CACHE_TTL = 900;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
        private OrderDetailsHydrator $hydrator,
    ) {}

    /**
     * @throws Exception
     */
    public function fetch(OrderGetByIdQuery $query): OrderDetails
    {
        $key = 'order_by_id_' . $query->orderId;

        /** @var OrderDetails|null $cached */
        $cached = $this->cacher->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $row = $this->connection->createQueryBuilder()
            ->select(...ReadModelFields::select(OrderDetails::fields(), 'o'))
            ->from(self::TABLE, 'o')
            ->where('o.id = :orderId')
            ->setParameter('orderId', $query->orderId)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            throw new DomainExceptionModule(
                module: 'order',
                message: 'error.order_not_found',
                code: 1,
            );
        }

        /** @var array{id: string, user_id: int|string, status: string, payment_status: string, total: int|string, bonus_used: int|string, bonus_earned: int|string, shipping_address: array{name?: string|null, phone?: string|null, email?: string|null, city?: string|null, address?: string|null, postal_code?: string|null, postalCode?: string|null}|string|null, payment_method: string, tracking_number: string|null, referred_by: string|null, created_at: string, paid_at: string|null, updated_at: string|null} $row */
        $order = $this->hydrator->hydrate([$row])[0];
        $this->cacher->setTagged($key, $order, self::CACHE_TTL, ['orders']);

        return $order;
    }
}
