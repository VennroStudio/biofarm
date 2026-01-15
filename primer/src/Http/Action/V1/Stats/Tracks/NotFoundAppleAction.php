<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Stats\Tracks;

use App\Modules\Query\Stats\Tracks\NotFoundApple\Fetcher;
use App\Modules\Query\Stats\Tracks\NotFoundApple\Query;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Components\Serializer\Denormalizer;
use ZayMedia\Shared\Components\Validator\Validator;
use ZayMedia\Shared\Http\Response\JsonDataResponse;

final readonly class NotFoundAppleAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
        private Denormalizer $denormalizer,
        private Validator $validator,
    ) {}

    public function handle(Request $request): Response
    {
        $query = $this->denormalizer->denormalizeQuery(
            data: $request->getQueryParams(),
            type: Query::class
        );

        $this->validator->validate($query);

        $result = $this->fetcher->fetch($query);

        return new JsonDataResponse($result);
    }
}
