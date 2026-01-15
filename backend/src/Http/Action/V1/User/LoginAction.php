<?php

declare(strict_types=1);

namespace App\Http\Action\V1\User;

use App\Modules\Query\Users\GetByEmail\Fetcher;
use App\Modules\Query\Users\GetByEmail\Query;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class LoginAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        $query = new Query(email: $email);
        $user = $this->fetcher->fetch($query);

        $response = new Response();
        
        // Проверяем существование пользователя
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Такого пользователя не существует'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // Проверяем пароль
        if (!password_verify($password, $user->getPasswordHash())) {
            $response->getBody()->write(json_encode(['error' => 'Не правильный пароль'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
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
            'createdAt' => date('c', $user->getCreatedAt()),
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
