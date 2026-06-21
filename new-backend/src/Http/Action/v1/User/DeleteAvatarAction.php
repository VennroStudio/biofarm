<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Modules\User\Command\User\DeleteAvatar\DeleteAvatarCommand;
use App\Modules\User\Command\User\DeleteAvatar\DeleteAvatarHandler;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OA\Delete(
    path: '/users/{id}/avatar',
    description: 'Удаление аватара пользователя',
    summary: 'Удалить аватар',
    security: [['bearerAuth' => []]],
    tags: ['Users'],
    parameters: [
        new OA\Parameter(
            name: 'id',
            description: 'ID пользователя',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer'),
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Аватар удалён'),
        new OA\Response(response: 401, description: 'Требуется авторизация'),
        new OA\Response(response: 403, description: 'Нет прав'),
        new OA\Response(response: 404, description: 'Пользователь не найден'),
        new OA\Response(response: 409, description: 'Аватар не установлен'),
    ],
)]
final readonly class DeleteAvatarAction implements RequestHandlerInterface
{
    public function __construct(
        private DeleteAvatarHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = Route::getArgumentToInt($request, 'id');
        $identity = RequestIdentity::get($request);

        $this->handler->handle(new DeleteAvatarCommand(
            userId: $userId,
            currentUserId: $identity->id,
            currentUserRole: $identity->role->value,
        ));

        return new JsonDataSuccessResponse(1, 200);
    }
}
