<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Withdrawal;

use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Id\ReadableIdGenerator;
use App\Modules\User\Entity\UserProfile\UserProfile;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\WithdrawalRequest;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\WithdrawalRequestRepository;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\RandomException;

final readonly class CreateWithdrawalAction implements RequestHandlerInterface
{
    public function __construct(
        private ReadableIdGenerator $idGenerator,
        private WithdrawalRequestRepository $repository,
        private UserProfileRepository $profileRepository,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $payload = (array)$request->getParsedBody();
        $amount = (int)($payload['amount'] ?? 0);

        $profile = $this->profileRepository->findByUserId($identity->id);
        if ($profile === null) {
            $profile = UserProfile::create(
                userId: $identity->id,
                referralCode: 'bf-' . $identity->id,
            );
            $this->profileRepository->add($profile);
        }

        if ($amount <= 0 || $amount > $profile->bonusBalance) {
            return new JsonDataResponse(['error' => 'invalid_withdrawal_amount'], 422);
        }

        $withdrawal = WithdrawalRequest::create(
            id: $this->idGenerator->generate('wd'),
            userId: $identity->id,
            amount: $amount,
        );

        $this->repository->add($withdrawal);
        $this->flusher->flush();

        return new JsonDataResponse([
            'id'         => $withdrawal->id,
            'user_id'    => $withdrawal->userId,
            'amount'     => $withdrawal->amount,
            'status'     => $withdrawal->status->value,
            'created_at' => $withdrawal->createdAt->format(DATE_ATOM),
        ], 201);
    }
}
