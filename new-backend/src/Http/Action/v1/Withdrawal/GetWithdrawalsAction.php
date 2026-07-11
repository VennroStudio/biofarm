<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Withdrawal;

use App\Components\Http\Middleware\Identity\RequestIdentity;
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
        $identity = RequestIdentity::get($request);
        $rows = $this->connection->createQueryBuilder()
            ->select('id', 'user_id', 'amount', 'status', 'processed_by', 'processed_at', 'created_at', 'updated_at')
            ->from('withdrawal_requests')
            ->where('user_id = :userId')
            ->setParameter('userId', $identity->id)
            ->orderBy('created_at', 'DESC')
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
            'updated_at'   => $row['updated_at'],
        ], $rows);

        return new JsonDataItemsResponse(count: \count($items), items: $items);
    }
}
