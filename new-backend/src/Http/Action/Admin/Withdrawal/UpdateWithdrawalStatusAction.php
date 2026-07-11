<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Withdrawal;

use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransaction;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransactionRepository;
use App\Modules\Bonus\Entity\BonusTransaction\Fields\Enums\BonusTransactionType;
use App\Modules\User\Entity\UserProfile\UserProfile;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\WithdrawalRequestRepository;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateWithdrawalStatusAction implements RequestHandlerInterface
{
    public function __construct(
        private WithdrawalRequestRepository $withdrawalRepository,
        private UserProfileRepository $profileRepository,
        private BonusTransactionRepository $bonusRepository,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $withdrawal = $this->withdrawalRepository->getById(Route::getArgument($request, 'id'));
        $payload = (array)$request->getParsedBody();
        $status = (string)($payload['status'] ?? '');
        $identity = RequestIdentity::get($request);
        $processedBy = $identity->firstName;

        if ($status === 'approved') {
            $withdrawal->approve($processedBy);
            $profile = $this->profileRepository->findByUserId($withdrawal->userId);
            if ($profile === null) {
                $profile = UserProfile::create($withdrawal->userId);
                $this->profileRepository->add($profile);
            }
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

        return new JsonDataSuccessResponse(1, 200);
    }
}
