<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\User;

use App\Components\Http\Response\JsonDataItemsResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetUsersAction implements RequestHandlerInterface
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
        $params = $request->getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = min(100, max(1, (int)($params['perPage'] ?? $params['per_page'] ?? 50)));
        $search = trim((string)($params['search'] ?? ''));

        $qb = $this->connection->createQueryBuilder()
            ->from('users', 'u')
            ->leftJoin('u', 'user_profiles', 'up', 'up.user_id = u.id')
            ->andWhere('u.deleted_at IS NULL');

        if ($search !== '') {
            $qb->andWhere("(u.email LIKE :search OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)")
                ->setParameter('search', '%' . $search . '%');
        }

        $countQb = clone $qb;
        $count = (int)$countQb->select('COUNT(u.id)')->executeQuery()->fetchOne();

        $rows = $qb
            ->select(
                'u.id',
                'u.role',
                'u.status',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.avatar',
                'u.created_at',
                'up.phone',
                'up.card_number',
                'COALESCE(up.bonus_balance, 0) AS bonus_balance',
                'COALESCE(up.is_partner, 0) AS is_partner',
                'up.referral_code',
                'up.referred_by_user_id',
            )
            ->orderBy('u.created_at', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->executeQuery()
            ->fetchAllAssociative();

        $items = array_map(static fn (array $row): array => [
            'id'                  => (int)$row['id'],
            'role'                => (int)$row['role'],
            'status'              => (int)$row['status'],
            'first_name'          => (string)$row['first_name'],
            'last_name'           => (string)$row['last_name'],
            'name'                => trim((string)$row['first_name'] . ' ' . (string)$row['last_name']),
            'email'               => (string)$row['email'],
            'avatar'              => $row['avatar'],
            'phone'               => $row['phone'],
            'card_number'         => $row['card_number'],
            'bonus_balance'       => (int)$row['bonus_balance'],
            'is_partner'          => (bool)(int)$row['is_partner'],
            'referral_code'       => $row['referral_code'],
            'referred_by_user_id' => $row['referred_by_user_id'] !== null ? (int)$row['referred_by_user_id'] : null,
            'created_at'          => (string)$row['created_at'],
        ], $rows);

        return new JsonDataItemsResponse(count: $count, items: $items);
    }
}
