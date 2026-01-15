<?php

declare(strict_types=1);

namespace App\Http\Action\V1\User;

use App\Http\Serializer;
use App\Modules\Query\Users\GetAll\Fetcher;
use App\Modules\Query\Users\GetAll\Query;
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
        $query = new Query();
        $users = $this->fetcher->fetch($query);

        $response = new Response();
        $response->getBody()->write(json_encode(
            array_map(fn($user) => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'phone' => $user->getPhone(),
                'bonusBalance' => $user->getBonusBalance(),
                'isPartner' => $user->isPartner(),
                'isActive' => $user->isActive(),
                'cardNumber' => $user->getCardNumber(),
                'createdAt' => date('c', $user->getCreatedAt()),
            ], $users),
            JSON_UNESCAPED_UNICODE
        ));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
