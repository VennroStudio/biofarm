<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Product;

use App\Modules\Command\Product\Delete\Command;
use App\Modules\Command\Product\Delete\Handler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class DeleteAction implements RequestHandlerInterface
{
    public function __construct(
        private Handler $handler,
        private EntityManagerInterface $em,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Try multiple ways to get the ID from route
        $route = $request->getAttribute('route');
        $productId = 0;
        
        if ($route) {
            $productId = (int)($route->getArgument('id') ?? 0);
        }
        
        // If still 0, try to get from URI (fallback for Slim routing issues)
        if ($productId === 0) {
            $path = $request->getUri()->getPath();
            if (preg_match('/\/products\/(\d+)/', $path, $matches)) {
                $productId = (int)$matches[1];
            }
        }
        
        if ($productId === 0) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Product ID is required'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $command = new Command(productId: $productId);

        try {
            $this->handler->handle($command);
            $this->em->flush();

            $response = new Response();
            $response->getBody()->write(json_encode(['success' => true], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
