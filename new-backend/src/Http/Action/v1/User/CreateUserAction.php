<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\User\Command\User\Create\CreateUserCommand;
use App\Modules\User\Command\User\Create\CreateUserHandler;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Translation\Translator;

#[OA\Post(
    path: '/users/create',
    description: 'Регистрация пользователя',
    summary: 'Регистрация пользователя',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['lastName', 'firstName', 'email', 'password'],
            properties: [
                new OA\Property(property: 'lastName', type: 'string', example: 'Иванов'),
                new OA\Property(property: 'firstName', type: 'string', example: 'Иван'),
                new OA\Property(property: 'email', type: 'string', example: 'vennro@gmail.com'),
                new OA\Property(property: 'password', type: 'string', example: 'Secret123!'),
            ]
        )
    ),
    tags: ['Users'],
    responses: [
        new OA\Response(response: 201, description: 'Пользователь создан'),
        new OA\Response(response: 409, description: 'Email уже зарегистрирован'),
        new OA\Response(response: 422, description: 'Ошибка валидации'),
    ]
)]
final readonly class CreateUserAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private CreateUserHandler $handler,
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
            CreateUserCommand::class,
        );

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse();
    }
}
