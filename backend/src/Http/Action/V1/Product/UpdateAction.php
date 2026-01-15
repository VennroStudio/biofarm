<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Product;

use App\Http\Serializer;
use App\Modules\Command\Product\Update\Command;
use App\Modules\Command\Product\Update\Handler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class UpdateAction implements RequestHandlerInterface
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
        
        $body = json_decode((string)$request->getBody(), true);

        $command = new Command(
            id: $productId,
            name: $body['name'] ?? '',
            categoryId: $body['category'] ?? $body['categoryId'] ?? '',
            price: (int)($body['price'] ?? 0),
            image: $body['image'] ?? '',
            weight: $body['weight'] ?? '',
            description: $body['description'] ?? '',
            shortDescription: $body['shortDescription'] ?? '',
            oldPrice: isset($body['oldPrice']) ? (int)$body['oldPrice'] : null,
            images: $body['images'] ?? null,
            badge: $body['badge'] ?? null,
            ingredients: $body['ingredients'] ?? null,
            features: $body['features'] ?? null,
            wbLink: $body['wbLink'] ?? null,
            ozonLink: $body['ozonLink'] ?? null,
            isActive: $body['isActive'] ?? true,
            slug: $body['slug'] ?? null,
        );

        try {
            $product = $this->handler->handle($command);
            $this->em->flush();

            $response = new Response();
            $response->getBody()->write(json_encode(
                Serializer::product($product),
                JSON_UNESCAPED_UNICODE
            ));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
