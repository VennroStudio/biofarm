<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Admin;

use App\Modules\Entity\Admin\AdminRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class LoginAction implements RequestHandlerInterface
{
    public function __construct(
        private AdminRepository $adminRepository,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        $response = new Response();
        
        if (empty($email) || empty($password)) {
            $response->getBody()->write(json_encode(['error' => 'Email и пароль обязательны'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $admin = $this->adminRepository->findByEmail($email);
        
        if (!$admin) {
            $response->getBody()->write(json_encode(['error' => 'Неверные данные для входа'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        if (!password_verify($password, $admin->getPasswordHash())) {
            $response->getBody()->write(json_encode(['error' => 'Неверные данные для входа'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'id' => $admin->getId(),
            'email' => $admin->getEmail(),
            'name' => $admin->getName(),
            'role' => $admin->getRole(),
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
