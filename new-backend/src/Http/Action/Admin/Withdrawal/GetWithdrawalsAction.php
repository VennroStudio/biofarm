<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Withdrawal;

use App\Components\Http\Response\JsonDataItemsResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetWithdrawalsAction implements RequestHandlerInterface
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
        $rows = $this->connection->createQueryBuilder()
            ->select(
                'wr.id',
                'wr.user_id',
                'wr.amount',
                'wr.status',
                'wr.processed_by',
                'wr.processed_at',
                'wr.created_at',
                'u.email',
                'u.first_name',
                'u.last_name',
                'up.card_number',
                'COALESCE(up.bonus_balance, 0) AS bonus_balance',
            )
            ->from('withdrawal_requests', 'wr')
            ->leftJoin('wr', 'users', 'u', 'u.id = wr.user_id')
            ->leftJoin('wr', 'user_profiles', 'up', 'up.user_id = wr.user_id')
            ->orderBy('wr.created_at', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        $items = array_map(static fn (array $row): array => [
            'id'           => (string)$row['id'],
            'user_id'      => (int)$row['user_id'],
            'amount'       => (int)$row['amount'],
            'status'       => (string)$row['status'],
            'processed_by' => $row['processed_by'],
            'processed_at' => $row['processed_at'],
            'created_at'   => (string)$row['created_at'],
            'user'         => [
                'email'         => $row['email'],
                'name'          => trim((string)$row['first_name'] . ' ' . (string)$row['last_name']),
                'card_number'   => $row['card_number'],
                'bonus_balance' => (int)$row['bonus_balance'],
            ],
        ], $rows);

        return new JsonDataItemsResponse(count: \count($items), items: $items);
    }
}
