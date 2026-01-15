<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Stats;

use App\Modules\Query\Stats\Artists\Fetcher;
use App\Modules\Query\Stats\Artists\FetcherCount;
use App\Modules\Query\Stats\Artists\Query;
use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use ZayMedia\Shared\Components\Serializer\Denormalizer;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

final readonly class ArtistsAction implements RequestHandlerInterface
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
                conflict: $query->conflict,
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
