<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Withdrawal;

use App\Modules\Command\Withdrawal\UpdateStatus\Command;
use App\Modules\Command\Withdrawal\UpdateStatus\Handler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class UpdateStatusAction implements RequestHandlerInterface
{
    public function __construct(
        private Handler $handler,
        private EntityManagerInterface $em,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Try multiple ways to get the ID from route
        $route = $request->getAttribute('route');
        $withdrawalId = '';
        
        if ($route) {
            $withdrawalId = $route->getArgument('id') ?? '';
        }
        
        // If still empty, try to get from URI (fallback for Slim routing issues)
        if ($withdrawalId === '') {
            $path = $request->getUri()->getPath();
            if (preg_match('/\/withdrawals\/([^\/]+)/', $path, $matches)) {
                $withdrawalId = $matches[1];
            }
        }
        
        if ($withdrawalId === '') {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Withdrawal ID is required'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $body = json_decode((string)$request->getBody(), true);

        $command = new Command(
            withdrawalId: $withdrawalId,
            status: $body['status'] ?? '',
            processedBy: $body['processedBy'] ?? null,
        );

        try {
            $withdrawal = $this->handler->handle($command);
            $this->em->flush();

            $response = new Response();
            $response->getBody()->write(json_encode([
                'id' => $withdrawal->getId(),
                'status' => $withdrawal->getStatus(),
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
