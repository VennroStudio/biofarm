<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Enum\EnumModel;
use App\Components\Http\Response\JsonDataResponse;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use JsonException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OA\Get(
    path: '/users/roles',
    description: 'Получение списка ролей пользователей',
    summary: 'Список ролей',
    security: [['bearerAuth' => []]],
    tags: ['Users'],
    responses: [
        new OA\Response(response: 200, description: 'Успешный ответ со списком'),
        new OA\Response(response: 401, description: 'Не авторизован'),
    ]
)]
final readonly class GetUserRolesAction implements RequestHandlerInterface
{
    /**
     * @throws JsonException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): JsonDataResponse
    {
        return new JsonDataResponse(EnumModel::fromEnumClass(UserRole::class));
    }
}