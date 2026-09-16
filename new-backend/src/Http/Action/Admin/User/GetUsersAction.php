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
                "(SELECT CONCAT(parent.first_name, ' ', parent.last_name) FROM users parent WHERE parent.id=up.referred_by_user_id) AS parent_name",
                '(SELECT COUNT(rp.user_id) FROM user_profiles rp WHERE rp.referred_by_user_id = u.id) AS referrals_count',
                '(SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE o.referred_by = up.referral_code OR o.referred_by = CAST(u.id AS CHAR)) AS referral_orders_total',
                "(SELECT JSON_ARRAYAGG(JSON_OBJECT(
                    'id', bt.id,
                    'amount', bt.amount,
                    'type', bt.type,
                    'source_order_id', bt.source_order_id,
                    'source_withdrawal_id', bt.source_withdrawal_id,
                    'comment', bt.comment,
                    'created_at', bt.created_at
                )) FROM bonus_transactions bt WHERE bt.user_id = u.id) AS bonus_transactions",
            )
            ->orderBy('u.created_at', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->executeQuery()
            ->fetchAllAssociative();

        $items = array_map(static fn (array $row): array => [
            'id'                    => (int)$row['id'],
            'role'                  => (int)$row['role'],
            'status'                => (int)$row['status'],
            'first_name'            => (string)$row['first_name'],
            'last_name'             => (string)$row['last_name'],
            'name'                  => trim((string)$row['first_name'] . ' ' . (string)$row['last_name']),
            'email'                 => (string)$row['email'],
            'avatar'                => $row['avatar'],
            'phone'                 => $row['phone'],
            'card_number'           => $row['card_number'],
            'bonus_balance'         => (int)$row['bonus_balance'],
            'is_partner'            => (bool)(int)$row['is_partner'],
            'referral_code'         => $row['referral_code'],
            'referred_by_user_id'   => $row['referred_by_user_id'] !== null ? (int)$row['referred_by_user_id'] : null,
            'is_referral'           => $row['referred_by_user_id'] !== null,
            'isReferral'            => $row['referred_by_user_id'] !== null,
            'parent_name'           => $row['parent_name'],
            'referrals_count'       => (int)$row['referrals_count'],
            'referral_orders_total' => (int)$row['referral_orders_total'],
            'bonus_transactions'    => self::bonusTransactions($row['bonus_transactions']),
            'created_at'            => (string)$row['created_at'],
        ], $rows);

        return new JsonDataItemsResponse(count: $count, items: $items);
    }

    /**
     * @return list<array{id: int, amount: int, type: string, source_order_id: string|null, source_withdrawal_id: string|null, comment: string|null, created_at: string}>
     */
    private static function bonusTransactions(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $items = \is_array($value) ? $value : json_decode((string)$value, true);
        if (!\is_array($items)) {
            return [];
        }

        $transactions = [];
        foreach ($items as $item) {
            if (!\is_array($item) || !isset($item['id'])) {
                continue;
            }

            $transactions[] = [
                'id'                   => (int)$item['id'],
                'amount'               => (int)($item['amount'] ?? 0),
                'type'                 => (string)($item['type'] ?? ''),
                'source_order_id'      => isset($item['source_order_id']) && $item['source_order_id'] !== '' ? (string)$item['source_order_id'] : null,
                'source_withdrawal_id' => isset($item['source_withdrawal_id']) && $item['source_withdrawal_id'] !== '' ? (string)$item['source_withdrawal_id'] : null,
                'comment'              => isset($item['comment']) && $item['comment'] !== '' ? (string)$item['comment'] : null,
                'created_at'           => (string)($item['created_at'] ?? ''),
            ];
        }

        usort($transactions, static fn (array $left, array $right): int => strcmp($right['created_at'], $left['created_at']));

        return $transactions;
    }
}
