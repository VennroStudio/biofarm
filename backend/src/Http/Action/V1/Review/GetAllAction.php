<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Review;

use App\Modules\Query\Reviews\GetAll\Fetcher;
use App\Modules\Query\Reviews\GetAll\Query;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class GetAllAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $onlyApproved = !isset($queryParams['all']) || $queryParams['all'] !== '1';

        $query = new Query(onlyApproved: $onlyApproved);
        $reviews = $this->fetcher->fetch($query);

        $response = new Response();
        $serialized = array_map([\App\Http\Serializer::class, 'review'], $reviews);
        $response->getBody()->write(json_encode($serialized, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
