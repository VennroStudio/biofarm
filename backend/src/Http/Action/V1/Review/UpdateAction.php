<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Review;

use App\Http\Serializer;
use App\Modules\Command\Review\Update\Command;
use App\Modules\Command\Review\Update\Handler;
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
        $reviewId = '';
        
        if ($route) {
            $reviewId = $route->getArgument('id') ?? '';
        }
        
        // If still empty, try to get from URI (fallback for Slim routing issues)
        if ($reviewId === '') {
            $path = $request->getUri()->getPath();
            if (preg_match('/\/reviews\/([^\/]+)/', $path, $matches)) {
                $reviewId = $matches[1];
            }
        }
        
        if ($reviewId === '') {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Review ID is required'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $body = json_decode((string)$request->getBody(), true);

        $command = new Command(
            reviewId: $reviewId,
            productId: (int)($body['productId'] ?? 0),
            userName: $body['userName'] ?? '',
            rating: (int)($body['rating'] ?? 5),
            text: $body['text'] ?? '',
            source: $body['source'] ?? 'site',
            userId: $body['userId'] ?? null,
            images: $body['images'] ?? null,
        );

        try {
            $review = $this->handler->handle($command);
            $this->em->flush();

            $response = new Response();
            $response->getBody()->write(json_encode(
                Serializer::review($review),
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
