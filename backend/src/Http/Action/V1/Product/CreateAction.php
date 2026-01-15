<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Product;

use App\Modules\Command\Product\Create\Command;
use App\Modules\Command\Product\Create\Handler;
use App\Utils\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class CreateAction implements RequestHandlerInterface
{
    public function __construct(
        private Handler $handler,
        private EntityManagerInterface $em,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);

        // Generate slug from name if not provided or invalid (only dashes)
        $slug = $body['slug'] ?? '';
        if (empty($slug) || trim($slug, '-') === '') {
            $slug = SlugGenerator::generate($body['name'] ?? '');
        }

        $command = new Command(
            slug: $slug,
            name: $body['name'] ?? '',
            categoryId: $body['categoryId'] ?? $body['category'] ?? '',
            price: (int)($body['price'] ?? 0),
            image: $body['image'] ?? '',
            weight: $body['weight'] ?? '',
            description: $body['description'] ?? '',
            shortDescription: $body['shortDescription'] ?? null,
            oldPrice: isset($body['oldPrice']) ? (int)$body['oldPrice'] : null,
            images: $body['images'] ?? null,
            badge: $body['badge'] ?? null,
            ingredients: $body['ingredients'] ?? null,
            features: $body['features'] ?? null,
            wbLink: $body['wbLink'] ?? null,
            ozonLink: $body['ozonLink'] ?? null,
            isActive: $body['isActive'] ?? true,
        );

        $product = $this->handler->handle($command);
        $this->em->flush();

        $response = new Response();
        $response->getBody()->write(json_encode(\App\Http\Serializer::product($product), JSON_UNESCAPED_UNICODE));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }
}
