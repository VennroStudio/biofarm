<?php

declare(strict_types=1);

namespace App\Modules\Order\Query\Order\FindAll;

use App\Components\Cacher\Cacher;
use App\Components\ReadModel\ModelCountItemsResult;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\Order\Query\Order\OrderDetailsHydrator;
use App\Modules\Order\ReadModel\Order\OrderDetails;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class OrderFindAllFetcher
{
    private const string TABLE = 'orders';
    private const int CACHE_TTL = 900;

    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
        private OrderDetailsHydrator $hydrator,
    ) {}

    /**
     * @return ModelCountItemsResult<OrderDetails>
     * @throws Exception
     */
    public function fetch(OrderFindAllQuery $query): ModelCountItemsResult
    {
        $key = \sprintf(
            'orders_find_all_%d_%d_%s_%s',
            $query->page,
            $query->perPage,
            $query->userId !== null ? (string)$query->userId : 'all',
            $query->referredBy !== null ? md5($query->referredBy) : 'none',
        );

        /** @var ModelCountItemsResult<OrderDetails>|null $cached */
        $cached = $this->cacher->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $qb = $this->connection->createQueryBuilder()->from(self::TABLE, 'o');

        if ($query->userId !== null) {
            $qb->andWhere('o.user_id = :userId')
                ->setParameter('userId', $query->userId);
        }

        if ($query->referredBy !== null && trim($query->referredBy) !== '') {
            $qb->andWhere('o.referred_by = :referredBy')
                ->setParameter('referredBy', trim($query->referredBy));
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(o.id)')->executeQuery()->fetchOne();

        $rows = $qb
            ->select(...ReadModelFields::select(OrderDetails::fields(), 'o'))
            ->orderBy('o.created_at', 'DESC')
            ->setFirstResult($query->getOffset())
            ->setMaxResults($query->perPage)
            ->executeQuery()
            ->fetchAllAssociative();

        $items = $this->hydrator->hydrate($rows);
        $result = new ModelCountItemsResult($items, $total);
        $this->cacher->setTagged($key, $result, self::CACHE_TTL, ['orders']);

        return $result;
    }
}
