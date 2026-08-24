<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Withdrawal;

use App\Components\Flusher\FlusherInterface;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Id\ReadableIdGenerator;
use App\Components\Setting\SiteSettings;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\WithdrawalRequest;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\WithdrawalRequestRepository;
use DateMalformedStringException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\RandomException;
use Throwable;

final readonly class CreateWithdrawalAction implements RequestHandlerInterface
{
    public function __construct(
        private ReadableIdGenerator $idGenerator,
        private WithdrawalRequestRepository $repository,
        private UserProfileRepository $profileRepository,
        private FlusherInterface $flusher,
        private SiteSettings $settings,
        private Connection $connection,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     * @throws RandomException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->settings->bool('withdrawals_enabled')) {
            throw new DomainExceptionModule('withdrawal', 'error.withdrawals_disabled', 4, status: 403);
        }

        $identity = RequestIdentity::get($request);
        $payload = (array)$request->getParsedBody();
        $amount = (int)($payload['amount'] ?? 0);

        $profile = $this->profileRepository->findByUserId($identity->id);
        if ($profile === null) {
            throw new DomainExceptionModule('withdrawal', 'error.invalid_withdrawal_amount', 3, status: 422);
        }

        $this->connection->beginTransaction();
        try {
            $balance = $this->lockBonusBalance($identity->id);
            $pendingAmount = $this->pendingAmount($identity->id);
            $availableAmount = max(0, $balance - $pendingAmount);

            if ($amount <= 0 || $amount > $availableAmount) {
                throw new DomainExceptionModule('withdrawal', 'error.invalid_withdrawal_amount', 3, status: 422);
            }

            $withdrawal = WithdrawalRequest::create(
                id: $this->idGenerator->generate('wd'),
                userId: $identity->id,
                amount: $amount,
            );

            $this->repository->add($withdrawal);
            $this->flusher->flush();
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }

        return new JsonDataResponse([
            'id'         => $withdrawal->id,
            'user_id'    => $withdrawal->userId,
            'amount'     => $withdrawal->amount,
            'status'     => $withdrawal->status->value,
            'created_at' => $withdrawal->createdAt->format(DATE_ATOM),
        ], 201);
    }

    /**
     * @throws Exception
     */
    private function lockBonusBalance(int $userId): int
    {
        $balance = $this->connection->fetchOne(
            'SELECT bonus_balance FROM user_profiles WHERE user_id = :userId FOR UPDATE',
            ['userId' => $userId],
        );

        return $balance !== false ? (int)$balance : 0;
    }

    /**
     * @throws Exception
     */
    private function pendingAmount(int $userId): int
    {
        return (int)$this->connection->fetchOne(
            "SELECT COALESCE(SUM(amount), 0)
             FROM withdrawal_requests
             WHERE user_id = :userId AND status = 'pending'",
            ['userId' => $userId],
        );
    }
}
