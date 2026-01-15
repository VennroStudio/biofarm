<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Category;

use App\Modules\Command\Category\Create\Command;
use App\Modules\Command\Category\Create\Handler;
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

        $slug = $body['slug'] ?? '';
        if (empty($slug)) {
            // Генерируем slug из названия, если не указан
            $slug = mb_strtolower(trim($body['name'] ?? ''));
            $slug = preg_replace('/[^a-zа-я0-9]+/u', '-', $slug);
            $slug = trim($slug, '-');
        }

        $command = new Command(
            slug: $slug,
            name: $body['name'] ?? '',
        );

        try {
            $category = $this->handler->handle($command);
            $this->em->flush();

            $response = new Response();
            $response->getBody()->write(json_encode(
                \App\Http\Serializer::category($category),
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
