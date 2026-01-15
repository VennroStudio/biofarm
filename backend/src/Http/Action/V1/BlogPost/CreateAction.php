<?php

declare(strict_types=1);

namespace App\Http\Action\V1\BlogPost;

use App\Http\Serializer;
use App\Modules\Command\BlogPost\Create\Command;
use App\Modules\Command\BlogPost\Create\Handler;
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

        // Generate slug if not provided or empty
        $slug = $body['slug'] ?? '';
        if (empty($slug)) {
            $slug = SlugGenerator::generate($body['title'] ?? '');
        }

        $command = new Command(
            slug: $slug,
            title: $body['title'] ?? '',
            excerpt: $body['excerpt'] ?? '',
            content: $body['content'] ?? '',
            image: $body['image'] ?? '',
            categoryId: $body['category'] ?? $body['categoryId'] ?? '',
            authorName: $body['authorName'] ?? 'Автор',
            readTime: (int)($body['readTime'] ?? 5),
            isPublished: $body['isPublished'] ?? true,
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
