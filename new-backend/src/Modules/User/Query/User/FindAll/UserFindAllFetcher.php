<?php

declare(strict_types=1);

namespace App\Modules\User\Query\User\FindAll;

use App\Components\ReadModel\ModelCountItemsResult;
use App\Modules\User\ReadModel\User\UserFindAll;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class UserFindAllFetcher
{
    private const string TABLE = 'users';

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @return ModelCountItemsResult<UserFindAll>
     * @throws Exception
     */
    public function fetch(UserFindAllQuery $query): ModelCountItemsResult
    {
        $qb = $this->connection->createQueryBuilder()
            ->from(self::TABLE)
            ->andWhere('deleted_at IS NULL');

        if ($query->search !== null && $query->search !== '') {
            $qb->andWhere("email ILIKE :search OR CONCAT(first_name, ' ', last_name) ILIKE :search")
                ->setParameter('search', '%' . $query->search . '%');
        }

        if ($query->dateFrom !== null) {
            $qb->andWhere('created_at >= :dateFrom')
                ->setParameter('dateFrom', $query->dateFrom . ' 00:00:00');
        }

        if ($query->dateTo !== null) {
            $qb->andWhere('created_at <= :dateTo')
                ->setParameter('dateTo', $query->dateTo . ' 23:59:59');
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(id)')->executeQuery()->fetchOne();

        $rows = $qb->select(
            'id',
            'status',
            'role',
            'first_name',
            'last_name',
            'email',
            'avatar',
            'created_at',
        )
            ->orderBy('created_at', 'DESC')
            ->setFirstResult($query->getOffset())
            ->setMaxResults($query->perPage)
            ->executeQuery()
            ->fetchAllAssociative();

        /** @var list<UserFindAll> $items */
        $items = UserFindAll::fromRows($rows);

        return new ModelCountItemsResult(
            items: $items,
            count: $total,
        );
    }
}
