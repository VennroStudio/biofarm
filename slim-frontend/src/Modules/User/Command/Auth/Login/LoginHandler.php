<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Auth\Login;

use App\Components\Auth\AccessTokenPayload;
use App\Components\Auth\JwtTokenService;
use App\Components\Auth\RefreshTokenPayload;
use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use App\Modules\User\Command\Auth\TokenPairResult;
use App\Modules\User\Command\UserToken\Create\CreateUserTokenCommand;
use App\Modules\User\Command\UserToken\Create\CreateUserTokenHandler;
use App\Modules\User\Entity\User\Fields\Enums\UserStatus;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use App\Modules\User\Query\User\FindByEmail\UserFindByEmailFetcher;
use App\Modules\User\Query\User\FindByEmail\UserFindByEmailQuery;
use App\Modules\User\ReadModel\User\UserByEmail;
use App\Modules\User\Service\PasswordHasherService;
use App\Modules\User\Service\TokenHasherService;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;

final readonly class LoginHandler
{
    public function __construct(
        private UserFindByEmailFetcher $userFindByEmailFetcher,
        private PasswordHasherService $passwordHasher,
        private JwtTokenService $jwtService,
        private CreateUserTokenHandler $createUserTokenHandler,
        private TokenHasherService $tokenHasher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function handle(LoginCommand $command): TokenPairResult
    {
        $user = $this->authenticate($command);

        $this->validateStatus($user);

        return $this->issueTokens($user);
    }

    /**
     * @throws Exception
     */
    private function authenticate(LoginCommand $command): UserByEmail
    {
        $email = mb_strtolower($command->email);
        $user = $this->userFindByEmailFetcher->fetchNotDeleted(new UserFindByEmailQuery($email));

        if ($user === null || !$this->passwordHasher->verify($command->password, $user->password)) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.invalid_credentials',
                code: 2
            );
        }

        return $user;
    }

    private function validateStatus(UserByEmail $user): void
    {
        if ($user->status === UserStatus::PENDING_VERIFICATION) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.email_not_verified',
                code: 3
            );
        }

        if ($user->status === UserStatus::BANNED) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.account_banned',
                code: 4
            );
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    private function issueTokens(UserByEmail $user): TokenPairResult
    {
        $accessPayload = AccessTokenPayload::issue(
            userId: $user->id,
            firstName: $user->firstName,
            role: $user->role,
            ttl: $this->jwtService->getAccessTtl(),
        );
        $refreshPayload = RefreshTokenPayload::issue(
            userId: $user->id,
            ttl: $this->jwtService->getRefreshTtl(),
        );

        $accessToken = $this->jwtService->generateAccessToken($accessPayload);
        $refreshToken = $this->jwtService->generateRefreshToken($refreshPayload);

        $this->createUserTokenHandler->handle(new CreateUserTokenCommand(
            userId: $user->id,
            type: UserTokenType::REFRESH,
            tokenHash: $this->tokenHasher->hash($refreshToken),
            expiresAt: UtcClock::fromTimestamp($refreshPayload->expiresAt),
        ));

        return new TokenPairResult(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresIn: $this->jwtService->getAccessTtl(),
        );
    }
}
