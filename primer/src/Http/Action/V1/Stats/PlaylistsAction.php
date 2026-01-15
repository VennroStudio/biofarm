<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Stats;

use App\Modules\Query\Stats\Playlists\Fetcher;
use App\Modules\Query\Stats\Playlists\FetcherCount;
use App\Modules\Query\Stats\Playlists\Query;
use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use ZayMedia\Shared\Components\Serializer\Denormalizer;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

final readonly class PlaylistsAction implements RequestHandlerInterface
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
            data: $request->getQueryParams(),
            type: Query::class
        );

        $this->validator->validate($query);

        $count = $this->fetcherCount->fetch(
            new Query(
                search: $query->search,
                type: $query->type,
                priority: $query->priority,
                isFollowed: $query->isFollowed,
                source: $query->source,
                field: $query->field,
                sort: $query->sort,
            )
        );

        $items = $this->fetcher->fetch($query);

        return new JsonDataResponse([
            'count' => $count,
            'items' => $items,
        ]);
    }
}
