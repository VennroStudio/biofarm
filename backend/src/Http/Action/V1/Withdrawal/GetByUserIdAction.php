<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Withdrawal;

use App\Modules\Query\Withdrawals\GetByUserId\Fetcher;
use App\Modules\Query\Withdrawals\GetByUserId\Query;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class GetByUserIdAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $userId = (int)($queryParams['userId'] ?? 0);

        $query = new Query(userId: $userId);
        $withdrawals = $this->fetcher->fetch($query);

        $response = new Response();
        $serialized = array_map([\App\Http\Serializer::class, 'withdrawal'], $withdrawals);
        $response->getBody()->write(json_encode($serialized, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
