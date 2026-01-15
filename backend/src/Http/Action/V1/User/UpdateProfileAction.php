<?php

declare(strict_types=1);

namespace App\Http\Action\V1\User;

use App\Modules\Command\User\Update\Command;
use App\Modules\Command\User\Update\Handler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class UpdateProfileAction implements RequestHandlerInterface
{
    public function __construct(
        private Handler $handler,
        private EntityManagerInterface $em,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);
        // TODO: Get user ID from session/token
        $userId = (int)($body['userId'] ?? 0);

        if (!$userId) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Получаем текущего пользователя для сохранения существующих значений
        $userRepository = $this->em->getRepository(\App\Modules\Entity\User\User::class);
        $currentUser = $userRepository->find($userId);
        
        if (!$currentUser) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'User not found'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $command = new Command(
            userId: $userId,
            name: $body['name'] ?? $currentUser->getName(),
            phone: $body['phone'] ?? $currentUser->getPhone(),
            cardNumber: $body['cardNumber'] ?? $currentUser->getCardNumber(),
            isPartner: isset($body['isPartner']) ? (bool)$body['isPartner'] : null,
            isActive: isset($body['isActive']) ? (bool)$body['isActive'] : null,
        );

        try {
            $user = $this->handler->handle($command);
            $this->em->flush();

            $response = new Response();
            $response->getBody()->write(json_encode([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'phone' => $user->getPhone(),
                'cardNumber' => $user->getCardNumber(),
                'isPartner' => $user->isPartner(),
                'isActive' => $user->isActive(),
                'bonusBalance' => $user->getBonusBalance(),
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
