<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Response\JsonDataItemsResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Http\Unifier\User\UserUnifier;
use App\Modules\User\Query\User\FindAll\UserFindAllFetcher;
use App\Modules\User\Query\User\FindAll\UserFindAllQuery;
use Doctrine\DBAL\Exception;
use JsonException;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Get(
    path: '/users',
    description: 'Получение списка пользователей с фильтрацией и пагинацией',
    summary: 'Список пользователей',
    security: [['bearerAuth' => []]],
    tags: ['Users'],
    parameters: [
        new OA\Parameter(name: 'page', description: 'Номер страницы', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        new OA\Parameter(name: 'perPage', description: 'Количество на странице', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        new OA\Parameter(name: 'search', description: 'Поиск по email или имени/фамилии', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'dateFrom', description: 'Дата регистрации от (Y-m-d)', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'dateTo', description: 'Дата регистрации до (Y-m-d)', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Успешный ответ со списком'),
        new OA\Response(response: 401, description: 'Не авторизован'),
        new OA\Response(response: 422, description: 'Ошибка валидации'),
    ]
)]
final readonly class GetUsersAction implements RequestHandlerInterface
{
    public function __construct(
        private UserFindAllFetcher $fetcher,
        private UserUnifier $unifier,
        private Denormalizer $denormalizer,
        private Validator $validator,
    ) {}

    /**
     * @throws ExceptionInterface
     * @throws JsonException
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $this->denormalizer->denormalize(
            $request->getQueryParams(),
            UserFindAllQuery::class,
        );

        $this->validator->validate($query);

        $result = $this->fetcher->fetch($query);

        return new JsonDataItemsResponse(
            count: $result->count,
            items: $this->unifier->unify(null, $result->items),
        );
    }
}
