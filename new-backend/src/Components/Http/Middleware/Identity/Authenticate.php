<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware\Identity;

use App\Components\Auth\JwtTokenService;
use App\Components\Exception\AuthenticationException;
use App\Components\Exception\DomainExceptionModule;
use App\Modules\User\Entity\User\Fields\Enums\UserStatus;
use App\Modules\User\Query\User\GetById\UserGetByIdFetcher;
use App\Modules\User\Query\User\GetById\UserGetByIdQuery;
use App\Modules\User\ReadModel\User\UserById;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class Authenticate implements MiddlewareInterface
{
    public function __construct(
        private JwtTokenService $jwtService,
        private UserGetByIdFetcher $userGetByIdFetcher,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle(RequestIdentity::with($request, $this->authenticate($request)));
    }

    /**
     * @throws Exception
     */
    public function authenticate(ServerRequestInterface $request): Identity
    {
        $payload = $this->jwtService->decodeAccessToken($this->extractBearerToken($request));
        $user = $this->getUser($payload->userId);

        return new Identity(
            id: $user->id,
            firstName: $user->firstName,
            role: $user->role,
        );
    }

    /**
     * @throws Exception
     */
    private function getUser(int $userId): UserById
    {
        try {
            $user = $this->userGetByIdFetcher->fetch(new UserGetByIdQuery($userId));
        } catch (DomainExceptionModule) {
            throw new AuthenticationException('error.unauthorized');
        }

        if ($user->status !== UserStatus::ACTIVE) {
            throw new AuthenticationException('error.unauthorized');
        }

        return $user;
    }

    private function extractBearerToken(ServerRequestInterface $request): string
    {
        $header = trim($request->getHeaderLine('Authorization'));

        if ($header === '' || !str_starts_with(strtolower($header), 'bearer ')) {
            throw new AuthenticationException('error.unauthorized');
        }

        $token = trim(substr($header, 7));

        if ($token === '') {
            throw new AuthenticationException('error.unauthorized');
        }

        return $token;
    }
}
