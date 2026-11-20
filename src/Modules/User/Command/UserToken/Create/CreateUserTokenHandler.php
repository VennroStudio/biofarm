<?php

declare(strict_types=1);

namespace App\Modules\User\Command\UserToken\Create;

use App\Components\Flusher\FlusherInterface;
use App\Modules\User\Entity\UserToken\UserToken;
use App\Modules\User\Entity\UserToken\UserTokenRepository;
use DateMalformedStringException;

final readonly class CreateUserTokenHandler
{
    public function __construct(
        private UserTokenRepository $userTokenRepository,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function handle(CreateUserTokenCommand $command): void
    {
        $userToken = UserToken::create(
            userId: $command->userId,
            type: $command->type,
            tokenHash: $command->tokenHash,
            expiresAt: $command->expiresAt,
        );
        $this->userTokenRepository->add($userToken);
        $this->flusher->flush();
    }
}
