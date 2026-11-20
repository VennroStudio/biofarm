<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Auth;

use App\Components\Auth\JwtTokenService;
use App\Components\Exception\AccessDeniedException;
use App\Components\Exception\AuthenticationException;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Cookie\AdminSessionCookie;
use App\Components\Http\Response\JsonDataResponse;
use App\Modules\User\Command\Auth\RefreshToken\RefreshTokenCommand;
use App\Modules\User\Command\Auth\RefreshToken\RefreshTokenHandler;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Query\User\GetById\UserGetByIdFetcher;
use App\Modules\User\Query\User\GetById\UserGetByIdQuery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class RefreshAction implements RequestHandlerInterface
{
    public function __construct(
        private AdminSessionCookie $cookie,
        private JwtTokenService $jwt,
        private UserGetByIdFetcher $users,
        private RefreshTokenHandler $handler,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->cookie->read($request);
        $payload = $this->jwt->decodeRefreshToken($token);
        try {
            $user = $this->users->fetch(new UserGetByIdQuery($payload->userId));
        } catch (DomainExceptionModule) {
            throw new AuthenticationException('Account is not active.');
        }
        if (!\in_array($user->role, [UserRole::ADMIN, UserRole::DEVELOPER, UserRole::EDITOR], true)) {
            throw new AccessDeniedException();
        }
        $result = $this->handler->handle(new RefreshTokenCommand($token));
        return $this->cookie->apply(new JsonDataResponse([
            'access_token' => $result->accessToken, 'expires_in' => $result->expiresIn,
        ]), $result->refreshToken);
    }
}
