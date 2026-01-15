<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Admin;

use App\Modules\Entity\Admin\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class ChangePasswordAction implements RequestHandlerInterface
{
    public function __construct(
        private AdminRepository $adminRepository,
        private EntityManagerInterface $em,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);
        $adminId = (int)($body['adminId'] ?? 0);
        $currentPassword = $body['currentPassword'] ?? '';
        $newPassword = $body['newPassword'] ?? '';

        $response = new Response();
        
        if (!$adminId || empty($currentPassword) || empty($newPassword)) {
            $response->getBody()->write(json_encode(['error' => 'Все поля обязательны'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (strlen($newPassword) < 4) {
            $response->getBody()->write(json_encode(['error' => 'Новый пароль должен быть не менее 4 символов'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $admin = $this->adminRepository->getById($adminId);
            
            // Проверяем текущий пароль
            if (!password_verify($currentPassword, $admin->getPasswordHash())) {
                $response->getBody()->write(json_encode(['error' => 'Неверный текущий пароль'], JSON_UNESCAPED_UNICODE));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
            
            // Обновляем пароль
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $admin->updatePassword($newPasswordHash);
            $this->em->flush();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Пароль успешно изменен',
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Admin Not Found'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    }
}
