<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\User\Command\User\Delete\DeleteUserCommand;
use App\Modules\User\Command\User\Delete\DeleteUserHandler;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Delete(
    path: '/users/delete/{id}',
    description: 'Мягкое удаление пользователя (админом или владельцем аккаунта)',
    summary: 'Удалить пользователя',
    security: [['bearerAuth' => []]],
    tags: ['Users'],
    parameters: [
        new OA\Parameter(
            name: 'id',
            description: 'ID пользователя',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer')
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Пользователь удалён'),
        new OA\Response(response: 401, description: 'Не авторизован'),
        new OA\Response(response: 403, description: 'Доступ запрещён'),
        new OA\Response(response: 404, description: 'Пользователь не найден'),
        new OA\Response(response: 409, description: 'Пользователь уже удалён'),
    ]
)]
final readonly class DeleteUserAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private DeleteUserHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = Route::getArgumentToInt($request, 'id');
        $identity = RequestIdentity::get($request);

        $command = $this->denormalizer->denormalize([
            'userId'          => $userId,
            'currentUserId'   => $identity->id,
            'currentUserRole' => $identity->role->value,
        ], DeleteUserCommand::class);

        $this->validator->validate($command);

        $this->handler->handle($command);

        return new JsonDataSuccessResponse(1, 200);
    }
}
