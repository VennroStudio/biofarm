<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Auth;

use App\Components\Http\Cookie\CookieContext;
use App\Components\Http\Cookie\CookieManager;
use App\Components\Http\Cookie\RequestCookies;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Validator\Validator;
use App\Modules\User\Command\Auth\RefreshToken\RefreshTokenCommand;
use App\Modules\User\Command\Auth\RefreshToken\RefreshTokenHandler;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;
use JsonException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OA\Post(
    path: '/auth/refresh',
    description: 'Обновление пары токенов по refreshToken из HTTP-Only Cookie (ротация)',
    summary: 'Refresh токенов',
    security: [['cookieAuth' => []]],
    tags: ['Auth'],
    responses: [
        new OA\Response(response: 200, description: 'Новая пара токенов'),
        new OA\Response(response: 401, description: 'Невалидный или отозванный токен'),
    ]
)]
final readonly class RefreshTokenAction implements RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private RefreshTokenHandler $handler,
        private CookieManager $cookieManager,
    ) {}

    /**
     * @throws Exception
     * @throws DateMalformedStringException
     * @throws JsonException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cookies = RequestCookies::get($request);

        $command = new RefreshTokenCommand(
            refreshToken: $cookies->refreshToken
        );

        $this->validator->validate($command);

        $result = $this->handler->handle($command);

        $response = new JsonDataResponse([
            'access_token' => $result->accessToken,
            'expires_in'   => $result->expiresIn,
        ]);

        return $this->cookieManager->apply(
            response: $response,
            context: new CookieContext(
                refreshToken: $result->refreshToken,
                loggedIn: '1'
            ),
        );
    }
}
