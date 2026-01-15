<?php

declare(strict_types=1);

namespace App\Http\Action\V1\User;

use App\Modules\Query\Users\GetReferralInfo\Fetcher;
use App\Modules\Query\Users\GetReferralInfo\Query;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class GetReferralInfoAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Try multiple ways to get the userId from route or query
        $route = $request->getAttribute('route');
        $userId = 0;
        
        if ($route) {
            $userId = (int)($route->getArgument('userId') ?? 0);
        }
        
        // If still 0, try to get from query params
        if ($userId === 0) {
            $queryParams = $request->getQueryParams();
            $userId = (int)($queryParams['userId'] ?? 0);
        }
        
        // If still 0, try to get from URI
        if ($userId === 0) {
            $path = $request->getUri()->getPath();
            if (preg_match('/\/referral-info\/(\d+)/', $path, $matches)) {
                $userId = (int)$matches[1];
            }
        }

        if ($userId === 0) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'User ID not found'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $query = new Query(userId: $userId);
        $info = $this->fetcher->fetch($query);

        $response = new Response();
        $response->getBody()->write(json_encode($info, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
