<?php

declare(strict_types=1);

namespace App\Http\Action\V1\BlogPost;

use App\Http\Serializer;
use App\Modules\Command\BlogPost\Update\Command;
use App\Modules\Command\BlogPost\Update\Handler;
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
        $postId = 0;
        
        if ($route) {
            $postId = (int)($route->getArgument('id') ?? 0);
        }
        
        // If still 0, try to get from URI (fallback for Slim routing issues)
        if ($postId === 0) {
            $path = $request->getUri()->getPath();
            if (preg_match('/\/blog\/(\d+)/', $path, $matches)) {
                $postId = (int)$matches[1];
            }
        }
        
        if ($postId === 0) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Post ID is required'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $body = json_decode((string)$request->getBody(), true);

        // Generate slug if not provided
        $slug = $body['slug'] ?? null;
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^\w\s-]/', '', $body['title'] ?? '')));
            $slug = preg_replace('/[-\s]+/', '-', $slug);
            $slug = trim($slug, '-');
        }

        $command = new Command(
            postId: $postId,
            title: $body['title'] ?? '',
            excerpt: $body['excerpt'] ?? '',
            content: $body['content'] ?? '',
            image: $body['image'] ?? '',
            categoryId: $body['category'] ?? $body['categoryId'] ?? '',
            authorName: $body['authorName'] ?? 'Автор',
            readTime: (int)($body['readTime'] ?? 5),
            isPublished: $body['isPublished'] ?? false,
            slug: $slug,
        );

        try {
            $post = $this->handler->handle($command);
            $this->em->flush();

            $response = new Response();
            $response->getBody()->write(json_encode(
                Serializer::blogPost($post),
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
