<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Auth;

use App\Components\Http\Cookie\CookieContext;
use App\Components\Http\Cookie\CookieManager;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\User\Command\Auth\Login\LoginCommand;
use App\Modules\User\Command\Auth\Login\LoginHandler;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;
use JsonException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Post(
    path: '/auth/login',
    description: 'Вход по email и паролю',
    summary: 'Логин',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'vennro@gmail.com'),
                new OA\Property(property: 'password', type: 'string', example: 'Secret123!'),
            ]
        )
    ),
    tags: ['Auth'],
    responses: [
        new OA\Response(response: 200, description: 'Успешный вход'),
        new OA\Response(response: 409, description: 'Неверные учётные данные или аккаунт не активен'),
        new OA\Response(response: 422, description: 'Ошибка валидации'),
    ]
)]
final readonly class LoginAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private LoginHandler $handler,
        private CookieManager $cookieManager,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     * @throws ExceptionInterface
     * @throws JsonException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = $this->denormalizer->denormalize(
            (array)$request->getParsedBody(),
            LoginCommand::class,
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
