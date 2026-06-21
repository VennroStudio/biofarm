<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Http\Unifier\User\UserUnifier;
use App\Modules\User\Query\User\GetById\UserGetByIdFetcher;
use App\Modules\User\Query\User\GetById\UserGetByIdQuery;
use Doctrine\DBAL\Exception;
use JsonException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OA\Get(
    path: '/users/{id}',
    description: 'Получение данных пользователя по ID (публичный доступ)',
    summary: 'Просмотр пользователя',
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
        new OA\Response(response: 200, description: 'Данные пользователя'),
        new OA\Response(response: 404, description: 'Пользователь не найден'),
    ]
)]
final readonly class GetUserByIdAction implements RequestHandlerInterface
{
    public function __construct(
        private UserGetByIdFetcher $fetcher,
        private UserUnifier $unifier,
    ) {}

    /**
     * @throws JsonException
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = Route::getArgumentToInt($request, 'id');

        $user = $this->fetcher->fetch(new UserGetByIdQuery($id));

        return new JsonDataResponse($this->unifier->unifyOne(null, $user));
    }
}
