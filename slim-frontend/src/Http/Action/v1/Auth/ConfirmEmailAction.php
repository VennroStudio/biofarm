<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Auth;

use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\User\Command\User\EmailConfirm\EmailConfirmCommand;
use App\Modules\User\Command\User\EmailConfirm\EmailConfirmHandler;
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
    path: '/auth/confirm-email',
    description: 'Подтверждение email по токену из ссылки в письме',
    summary: 'Подтвердить email',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['token'],
            properties: [
                new OA\Property(property: 'token', description: 'Токен из ссылки письма (query-параметр)', type: 'string'),
            ]
        )
    ),
    tags: ['Auth'],
    responses: [
        new OA\Response(response: 200, description: 'Email подтверждён, аккаунт активирован'),
        new OA\Response(response: 409, description: 'Невалидная или истёкшая ссылка'),
        new OA\Response(response: 422, description: 'Ошибка валидации'),
    ]
)]
final readonly class ConfirmEmailAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private EmailConfirmHandler $handler,
    ) {}

    /**
     * @throws ExceptionInterface
     * @throws JsonException
     * @throws DateMalformedStringException
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = $this->denormalizer->denormalize(
            (array)$request->getParsedBody(),
            EmailConfirmCommand::class,
        );

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse(1, 200);
    }
}
