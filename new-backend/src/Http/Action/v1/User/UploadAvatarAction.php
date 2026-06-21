<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Exception\AccessDeniedException;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Request\RequestFile;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Modules\User\Command\User\UploadAvatar\UploadAvatarCommand;
use App\Modules\User\Command\User\UploadAvatar\UploadAvatarHandler;
use DateMalformedStringException;
use JsonException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\RandomException;

#[OA\Post(
    path: '/users/{id}/avatar',
    description: 'Загрузка аватара пользователя (multipart/form-data)',
    summary: 'Загрузить аватар',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['avatar'],
                properties: [
                    new OA\Property(
                        property: 'avatar',
                        description: 'Файл изображения',
                        type: 'string',
                        format: 'binary',
                    ),
                ],
            ),
        ),
    ),
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
        new OA\Response(
            response: 200,
            description: 'Аватар загружен',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'avatar', type: 'string', example: 'https://s3.example.com/user/1/avatar/a1b2c3d4.jpg'),
                ],
            )
        ),
        new OA\Response(response: 401, description: 'Требуется авторизация'),
        new OA\Response(response: 403, description: 'Нет прав'),
        new OA\Response(response: 409, description: 'Неверный формат файла или файл слишком большой'),
        new OA\Response(response: 404, description: 'Пользователь не найден'),
    ],
)]
final readonly class UploadAvatarAction implements RequestHandlerInterface
{
    public function __construct(
        private UploadAvatarHandler $handler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws AccessDeniedException
     * @throws JsonException
     * @throws RandomException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = Route::getArgumentToInt($request, 'id');
        $identity = RequestIdentity::get($request);
        $file = RequestFile::extract($request, 'avatar');

        if ($file === null) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.avatar_file_required',
                code: 13,
            );
        }

        $url = $this->handler->handle(new UploadAvatarCommand(
            userId: $userId,
            currentUserId: $identity->id,
            currentUserRole: $identity->role->value,
            tmpFilePath: $file->getPath(),
        ));

        return new JsonDataResponse(['avatar' => $url], 200);
    }
}
