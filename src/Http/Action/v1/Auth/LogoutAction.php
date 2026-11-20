<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Auth;

use App\Components\Http\Cookie\CookieContext;
use App\Components\Http\Cookie\CookieManager;
use App\Components\Http\Cookie\RequestCookies;
use App\Modules\User\Command\Auth\Logout\LogoutCommand;
use App\Modules\User\Command\Auth\Logout\LogoutHandler;
use Exception;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

#[OA\Post(
    path: '/auth/logout',
    description: 'Выход — инвалидация refresh-токена из Cookie',
    summary: 'Логаут',
    security: [['cookieAuth' => []]],
    tags: ['Auth'],
    responses: [
        new OA\Response(response: 204, description: 'Успешный выход'),
    ]
)]
final readonly class LogoutAction implements RequestHandlerInterface
{
    public function __construct(
        private LogoutHandler $handler,
        private CookieManager $cookieManager,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cookies = RequestCookies::get($request);

        $command = new LogoutCommand(
            refreshToken: $cookies->refreshToken
        );
        $this->handler->handle($command);

        return $this->cookieManager->discard(new Response(204), new CookieContext());
    }
}
