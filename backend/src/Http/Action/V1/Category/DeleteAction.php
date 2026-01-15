<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Category;

use App\Modules\Command\Category\Delete\Command;
use App\Modules\Command\Category\Delete\Handler;
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
        $route = $request->getAttribute('route');
        $categoryId = (int)($route?->getArgument('id') ?? 0);

        // Fallback for Slim routing issues if ID is not extracted
        if ($categoryId === 0) {
            $path = $request->getUri()->getPath();
            if (preg_match('/\/categories\/(\d+)/', $path, $matches)) {
                $categoryId = (int)$matches[1];
            }
        }

        if ($categoryId === 0) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Category ID not found in route or URI'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $command = new Command(categoryId: $categoryId);

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
