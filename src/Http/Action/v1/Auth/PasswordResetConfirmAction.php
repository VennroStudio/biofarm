<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Auth;

use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\User\Command\Auth\PasswordResetConfirm\PasswordResetConfirmCommand;
use App\Modules\User\Command\Auth\PasswordResetConfirm\PasswordResetConfirmHandler;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Post(
    path: '/auth/password-reset/confirm',
    description: 'Подтверждение восстановления пароля',
    summary: 'Подтверждение восстановления пароля (ввод нового пароля по токену)',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['token', 'password'],
            properties: [
                new OA\Property(property: 'token', type: 'string', example: 'abc123token...'),
                new OA\Property(property: 'password', type: 'string', example: 'NewSecret123!'),
            ]
        )
    ),
    tags: ['Auth'],
    responses: [
        new OA\Response(response: 201, description: 'Пароль успешно изменен'),
        new OA\Response(response: 409, description: 'Невалидный токен или срок его действия истек'),
        new OA\Response(response: 422, description: 'Ошибка валидации'),
    ]
)]
final readonly class PasswordResetConfirmAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private PasswordResetConfirmHandler $handler,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = $this->denormalizer->denormalize(
            (array)$request->getParsedBody(),
            PasswordResetConfirmCommand::class,
        );

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
