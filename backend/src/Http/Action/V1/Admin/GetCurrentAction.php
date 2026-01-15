<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Admin;

use App\Modules\Entity\Admin\AdminRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class GetCurrentAction implements RequestHandlerInterface
{
    public function __construct(
        private AdminRepository $adminRepository,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adminId = (int)($request->getQueryParams()['adminId'] ?? 0);

        $response = new Response();
        
        if (!$adminId) {
            $response->getBody()->write(json_encode(['error' => 'Admin ID is required'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $admin = $this->adminRepository->getById($adminId);
            
            $response->getBody()->write(json_encode([
                'id' => $admin->getId(),
                'email' => $admin->getEmail(),
                'name' => $admin->getName(),
                'role' => $admin->getRole(),
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Admin Not Found'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    }
}
