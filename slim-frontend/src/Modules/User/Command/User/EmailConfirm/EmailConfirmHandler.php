<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\EmailConfirm;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use App\Modules\User\Entity\UserToken\UserToken;
use App\Modules\User\Entity\UserToken\UserTokenRepository;
use App\Modules\User\Query\UserToken\FindByHash\UserTokenFindByHashFetcher;
use App\Modules\User\Query\UserToken\FindByHash\UserTokenFindByHashQuery;
use App\Modules\User\Service\TokenHasherService;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;

final readonly class EmailConfirmHandler
{
    public function __construct(
        private TokenHasherService $tokenHasher,
        private UserTokenFindByHashFetcher $userTokenByHashFetcher,
        private UserTokenRepository $userTokenRepository,
        private UserRepository $userRepository,
        private FlusherInterface $flusher,
        private Cacher $cacher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function handle(EmailConfirmCommand $command): void
    {
        $userToken = $this->findValidToken($command->token);
        $user = $this->userRepository->getById($userToken->userId);

        $user->activate();

        $this->cacher->delete('user_identity_' . $user->id);

        $this->flusher->flush();
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    private function findValidToken(string $plainToken): UserToken
    {
        $tokenHash = $this->tokenHasher->hash($plainToken);
        $tokenByHash = $this->userTokenByHashFetcher->fetch(
            new UserTokenFindByHashQuery($tokenHash, UserTokenType::EMAIL_VERIFICATION)
        );

        if ($tokenByHash === null) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.invalid_or_expired_confirm_link',
                code: 7
            );
        }

        $userToken = $this->userTokenRepository->getById($tokenByHash->id);

        if (!$userToken->markUsed()) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.invalid_or_expired_confirm_link',
                code: 7
            );
        }

        $this->cacher->delete('user_token_' . $userToken->tokenHash);

        return $userToken;
    }
}
