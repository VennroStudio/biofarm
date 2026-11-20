<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Auth\PasswordResetConfirm;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Modules\User\Entity\User\User;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use App\Modules\User\Entity\UserToken\UserToken;
use App\Modules\User\Entity\UserToken\UserTokenRepository;
use App\Modules\User\Query\UserToken\FindByHash\UserTokenFindByHashFetcher;
use App\Modules\User\Query\UserToken\FindByHash\UserTokenFindByHashQuery;
use App\Modules\User\Service\PasswordHasherService;
use App\Modules\User\Service\TokenHasherService;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;

final readonly class PasswordResetConfirmHandler
{
    public function __construct(
        private TokenHasherService $tokenHasher,
        private PasswordHasherService $passwordHasher,
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
    public function handle(PasswordResetConfirmCommand $command): void
    {
        $userToken = $this->getUserToken($command->token);

        $user = $this->userRepository->getById($userToken->userId);

        $this->resetPassword($user, $command->password);
        $this->revokeRefreshTokens((int)$user->id);

        $this->cacher->delete('user_identity_' . (int)$user->id);

        $this->flusher->flush();
    }

    /**
     * @throws Exception
     * @throws DateMalformedStringException
     */
    private function getUserToken(string $plainToken): UserToken
    {
        $tokenHash = $this->tokenHasher->hash($plainToken);

        $tokenByHash = $this->userTokenByHashFetcher->fetch(
            new UserTokenFindByHashQuery($tokenHash, UserTokenType::PASSWORD_RESET)
        );

        if ($tokenByHash === null) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.invalid_or_expired_reset_link',
                code: 6
            );
        }

        $userToken = $this->userTokenRepository->getById($tokenByHash->id);

        if (!$userToken->markUsed()) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.invalid_or_expired_reset_link',
                code: 6
            );
        }

        $this->cacher->delete('user_token_' . $userToken->tokenHash);

        return $userToken;
    }

    private function resetPassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hash($plainPassword));
    }

    /**
     * @throws DateMalformedStringException
     */
    private function revokeRefreshTokens(int $userId): void
    {
        foreach ($this->userTokenRepository->findByUserIdAndType($userId, UserTokenType::REFRESH) as $token) {
            $token->revoke();
            $this->cacher->delete('user_token_' . $token->tokenHash);
        }
    }
}
