<?php

declare(strict_types=1);

namespace App\Http\Action\V1\User;

use App\Modules\Command\User\Create\Command;
use App\Modules\Command\User\Create\Handler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class RegisterAction implements RequestHandlerInterface
{
    public function __construct(
        private Handler $handler,
        private EntityManagerInterface $em,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);

        $command = new Command(
            email: $body['email'] ?? '',
            name: $body['name'] ?? '',
            passwordHash: password_hash($body['password'] ?? '', PASSWORD_DEFAULT),
            phone: $body['phone'] ?? null,
            referredBy: $body['referredBy'] ?? null,
        );

        try {
            $user = $this->handler->handle($command);
            $this->em->flush(); // Сохраняем пользователя в БД перед получением ID

            $response = new Response();
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
        } catch (\DomainException $e) {
            $response = new Response();
            // Проверяем, если это ошибка о существующем пользователе
            if (str_contains($e->getMessage(), 'already exists')) {
                $response->getBody()->write(json_encode(['error' => 'Пользователь с таким email уже зарегистрирован'], JSON_UNESCAPED_UNICODE));
            } else {
                $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
            }
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
