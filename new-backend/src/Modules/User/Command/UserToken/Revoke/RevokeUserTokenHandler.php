<?php

declare(strict_types=1);

namespace App\Modules\User\Command\UserToken\Revoke;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Modules\User\Entity\UserToken\UserTokenRepository;
use App\Modules\User\Query\UserToken\FindByHash\UserTokenFindByHashFetcher;
use App\Modules\User\Query\UserToken\FindByHash\UserTokenFindByHashQuery;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;

final readonly class RevokeUserTokenHandler
{
    public function __construct(
        private UserTokenFindByHashFetcher $userTokenByHashFetcher,
        private UserTokenRepository $userTokenRepository,
        private FlusherInterface $flusher,
        private Cacher $cacher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function handle(RevokeUserTokenCommand $command): void
    {
        $tokenByHash = $this->userTokenByHashFetcher->fetch(
            new UserTokenFindByHashQuery($command->tokenHash, $command->type)
        );

        if ($tokenByHash === null) {
            return;
        }

        $userToken = $this->userTokenRepository->getById($tokenByHash->id);
        $userToken->revoke();
        $this->flusher->flush();

        $this->cacher->delete('user_token_' . $command->tokenHash);
    }
}
