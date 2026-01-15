<?php

declare(strict_types=1);

namespace App\Http\Action\V1\User;

use App\Modules\Query\Users\GetById\Fetcher;
use App\Modules\Query\Users\GetById\Query;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class GetCurrentAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: Get user ID from session/token
        $userId = (int)($request->getQueryParams()['userId'] ?? 0);

        if (!$userId) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $query = new Query(userId: $userId);
        $user = $this->fetcher->fetch($query);

        $response = new Response();
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'User not found'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'phone' => $user->getPhone(),
            'bonusBalance' => $user->getBonusBalance(),
            'isPartner' => $user->isPartner(),
            'isActive' => $user->isActive(),
            'cardNumber' => $user->getCardNumber(),
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
