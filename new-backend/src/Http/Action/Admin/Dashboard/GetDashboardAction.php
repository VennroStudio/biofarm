<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Dashboard;

use App\Components\Http\Response\JsonDataResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetDashboardAction implements RequestHandlerInterface
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
        $totalOrders = (int)$this->connection->fetchOne('SELECT COUNT(id) FROM orders');
        $totalRevenue = (int)$this->connection->fetchOne(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'completed' OR paid_at IS NOT NULL"
        );
        $totalUsers = (int)$this->connection->fetchOne('SELECT COUNT(id) FROM users WHERE deleted_at IS NULL');
        $pendingWithdrawals = (int)$this->connection->fetchOne(
            "SELECT COUNT(id) FROM withdrawal_requests WHERE status = 'pending'"
        );
        $totalWithdrawalAmount = (int)$this->connection->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) FROM withdrawal_requests WHERE status = 'pending'"
        );

        return new JsonDataResponse([
            'total_orders'            => $totalOrders,
            'total_revenue'           => $totalRevenue,
            'total_users'             => $totalUsers,
            'pending_withdrawals'     => $pendingWithdrawals,
            'total_withdrawal_amount' => $totalWithdrawalAmount,
        ]);
    }
}
