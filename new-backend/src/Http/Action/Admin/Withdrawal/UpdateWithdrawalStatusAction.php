<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Withdrawal;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransaction;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransactionRepository;
use App\Modules\Bonus\Entity\BonusTransaction\Fields\Enums\BonusTransactionType;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\WithdrawalRequestRepository;
use DateMalformedStringException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final readonly class UpdateWithdrawalStatusAction implements RequestHandlerInterface
{
    public function __construct(
        private WithdrawalRequestRepository $withdrawalRepository,
        private UserProfileRepository $profileRepository,
        private BonusTransactionRepository $bonusRepository,
        private FlusherInterface $flusher,
        private Connection $connection,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $withdrawal = $this->withdrawalRepository->getById(Route::getArgument($request, 'id'));
        $payload = (array)$request->getParsedBody();
        $status = (string)($payload['status'] ?? '');
        $identity = RequestIdentity::get($request);
        $processedBy = $identity->firstName;

        if ($status !== 'approved' && $status !== 'rejected') {
            throw new DomainExceptionModule('withdrawal', 'error.invalid_withdrawal_status', 5, status: 422);
        }

        $this->connection->beginTransaction();
        try {
            if ($status === 'approved') {
                $balance = $this->lockBonusBalance($withdrawal->userId);
                if ($balance < $withdrawal->amount) {
                    throw new DomainExceptionModule(
                        module: 'withdrawal',
                        message: 'error.invalid_withdrawal_amount',
                        code: 3,
                        status: 422,
                    );
                }

                $withdrawal->approve($processedBy);
                $profile = $this->profileRepository->getByUserId($withdrawal->userId);
                $profile->addBonus(-$withdrawal->amount);
                $this->bonusRepository->add(BonusTransaction::create(
                    userId: $withdrawal->userId,
                    amount: -$withdrawal->amount,
                    type: BonusTransactionType::WITHDRAWAL,
                    sourceWithdrawalId: $withdrawal->id,
                ));
            } elseif ($status === 'rejected') {
                $withdrawal->reject($processedBy);
            }

            $this->flusher->flush();
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }

        return new JsonDataSuccessResponse(1, 200);
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
}
