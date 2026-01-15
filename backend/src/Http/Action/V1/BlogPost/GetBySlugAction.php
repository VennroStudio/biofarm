<?php

declare(strict_types=1);

namespace App\Http\Action\V1\BlogPost;

use App\Modules\Query\BlogPosts\GetBySlug\Fetcher;
use App\Modules\Query\BlogPosts\GetBySlug\Query;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class GetBySlugAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Try multiple ways to get the slug from route
        $route = $request->getAttribute('route');
        $slug = '';
        
        if ($route) {
            $slug = $route->getArgument('slug') ?? '';
        }
        
        // If still empty, try to get from URI (fallback for Slim routing issues)
        if ($slug === '') {
            $path = $request->getUri()->getPath();
            if (preg_match('/\/blog\/([^\/]+)/', $path, $matches)) {
                $slug = $matches[1];
            }
        }

        $query = new Query(slug: $slug);
        $post = $this->fetcher->fetch($query);

        $response = new Response();
        if (!$post) {
            $response->getBody()->write(json_encode(['error' => 'Post not found'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(\App\Http\Serializer::blogPost($post), JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
