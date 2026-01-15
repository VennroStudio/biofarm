<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Stats;

use App\Modules\Query\ArtistProblematic\Fetcher;
use App\Modules\Query\ArtistProblematic\FetcherCount;
use App\Modules\Query\ArtistProblematic\Query;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use ZayMedia\Shared\Components\Serializer\Denormalizer;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Helpers\OpenApi\ResponseSuccessful;
use ZayMedia\Shared\Helpers\OpenApi\Security;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

#[OA\Get(
    path: '/stats/artist-problematic',
    description: 'Получение списка проблематичных артистов с возможностью фильтрации и сортировки',
    summary: 'Список проблематичных артистов',
    security: [Security::BEARER_AUTH],
    tags: ['ArtistProblematic'],
    responses: [new ResponseSuccessful()]
)]
#[OA\Parameter(
    name: 'search',
    description: 'Поиск по имени артиста',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string', maxLength: 255)
)]
#[OA\Parameter(
    name: 'status',
    description: 'Фильтр по статусу артиста',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer')
)]
#[OA\Parameter(
    name: 'field',
    description: 'Поле для сортировки',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string', default: 'id'),
    example: 'id'
)]
#[OA\Parameter(
    name: 'sort',
    description: 'Направление сортировки: 0 - DESC, 1 - ASC',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 1, enum: [0, 1]),
    example: 1
)]
#[OA\Parameter(
    name: 'count',
    description: 'Количество записей на странице',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 50),
    example: 50
)]
#[OA\Parameter(
    name: 'offset',
    description: 'Смещение для пагинации',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 0),
    example: 0
)]
final readonly class ArtistProblematicAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
        private FetcherCount $fetcherCount,
        private Denormalizer $denormalizer,
        private Validator $validator,
    ) {}

    /** @throws Exception|ExceptionInterface */
    public function handle(Request $request): Response
    {
        $query = $this->denormalizer->denormalizeQuery(
            $request->getQueryParams(),
            type: Query::class
        );

        $this->validator->validate($query);

        $count = $this->fetcherCount->fetch($query);
        $items = $this->fetcher->fetch($query);

        return new JsonDataResponse([
            'count' => $count,
            'items' => $items,
        ]);
    }
}
