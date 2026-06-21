<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Auth;

use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\User\Command\Auth\PasswordReset\PasswordResetCommand;
use App\Modules\User\Command\Auth\PasswordReset\PasswordResetHandler;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Translation\Translator;

#[OA\Post(
    path: '/auth/password-reset',
    description: 'Запрос на восстановление пароля',
    summary: 'Запрос на восстановление пароля',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'vennro@gmail.com'),
            ]
        )
    ),
    tags: ['Auth'],
    responses: [
        new OA\Response(response: 201, description: 'Запрос на восстановление создан, письмо отправлено'),
        new OA\Response(response: 409, description: 'Пользователь не найден или заблокирован'),
        new OA\Response(response: 422, description: 'Ошибка валидации'),
    ]
)]
final readonly class PasswordResetAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private PasswordResetHandler $handler,
        private Translator $translator,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = $this->denormalizer->denormalize(
            array_merge(
                (array)$request->getParsedBody(),
                ['locale' => $this->translator->getLocale()],
            ),
            PasswordResetCommand::class,
        );

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
