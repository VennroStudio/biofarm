<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Auth\RefreshToken;

use App\Components\Auth\AccessTokenPayload;
use App\Components\Auth\JwtTokenService;
use App\Components\Auth\RefreshTokenPayload;
use App\Components\Cacher\Cacher;
use App\Components\Clock\UtcClock;
use App\Components\Exception\AuthenticationException;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Modules\User\Command\Auth\TokenPairResult;
use App\Modules\User\Entity\User\Fields\Enums\UserStatus;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use App\Modules\User\Entity\UserToken\UserToken;
use App\Modules\User\Entity\UserToken\UserTokenRepository;
use App\Modules\User\Query\User\GetById\UserGetByIdFetcher;
use App\Modules\User\Query\User\GetById\UserGetByIdQuery;
use App\Modules\User\Query\UserToken\FindByHash\UserTokenFindByHashFetcher;
use App\Modules\User\Query\UserToken\FindByHash\UserTokenFindByHashQuery;
use App\Modules\User\ReadModel\User\UserById;
use App\Modules\User\Service\TokenHasherService;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;

final readonly class RefreshTokenHandler
{
    public function __construct(
        private JwtTokenService $jwtService,
        private UserTokenFindByHashFetcher $userTokenByHashFetcher,
        private TokenHasherService $tokenHasher,
        private UserTokenRepository $userTokenRepository,
        private UserGetByIdFetcher $userGetByIdFetcher,
        private FlusherInterface $flusher,
        private Cacher $cacher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function handle(RefreshTokenCommand $command): TokenPairResult
    {
        $payload = $this->decodeToken($command->refreshToken);
        $userToken = $this->findValidToken($command->refreshToken);
        $user = $this->getUser($payload->userId);

        return $this->rotateTokenPair($userToken, $payload->userId, $user);
    }

    private function decodeToken(string $refreshToken): RefreshTokenPayload
    {
        return $this->jwtService->decodeRefreshToken($refreshToken);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    private function findValidToken(string $refreshToken): UserToken
    {
        $tokenHash = $this->tokenHasher->hash($refreshToken);
        $tokenByHash = $this->userTokenByHashFetcher->fetch(
            new UserTokenFindByHashQuery($tokenHash, UserTokenType::REFRESH)
        );

        if ($tokenByHash === null) {
            throw new AuthenticationException('Token revoked.');
        }

        $userToken = $this->userTokenRepository->getById($tokenByHash->id);
        if (!$userToken->isActive()) {
            throw new AuthenticationException('Invalid or expired token.');
        }

        return $userToken;
    }

    /**
     * @throws Exception
     */
    private function getUser(int $userId): UserById
    {
        try {
            $user = $this->userGetByIdFetcher->fetch(new UserGetByIdQuery($userId));
        } catch (DomainExceptionModule) {
            throw new AuthenticationException('Account is not active.');
        }

        if ($user->status !== UserStatus::ACTIVE) {
            throw new AuthenticationException('Account is not active.');
        }

        return $user;
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    private function rotateTokenPair(UserToken $currentToken, int $userId, UserById $user): TokenPairResult
    {
        $accessPayload = AccessTokenPayload::issue(
            userId: $userId,
            firstName: $user->firstName,
            role: $user->role,
            ttl: $this->jwtService->getAccessTtl(),
        );
        $refreshPayload = RefreshTokenPayload::issue(
            userId: $userId,
            ttl: $this->jwtService->getRefreshTtl(),
        );

        $accessToken = $this->jwtService->generateAccessToken($accessPayload);
        $refreshToken = $this->jwtService->generateRefreshToken($refreshPayload);

        $currentToken->revoke();
        $this->cacher->delete('user_token_' . $currentToken->tokenHash);

        $this->userTokenRepository->add(UserToken::create(
            userId: $userId,
            type: UserTokenType::REFRESH,
            tokenHash: $this->tokenHasher->hash($refreshToken),
            expiresAt: UtcClock::fromTimestamp($refreshPayload->expiresAt),
        ));
        $this->flusher->flush();

        return new TokenPairResult(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresIn: $this->jwtService->getAccessTtl(),
        );
    }
}
