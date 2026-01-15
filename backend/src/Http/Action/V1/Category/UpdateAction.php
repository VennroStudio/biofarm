<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Category;

use App\Modules\Command\Category\Update\Command;
use App\Modules\Command\Category\Update\Handler;
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

        $body = json_decode((string)$request->getBody(), true);

        $slug = $body['slug'] ?? null;
        if ($slug !== null && empty($slug)) {
            // Генерируем slug из названия, если не указан
            $slug = mb_strtolower(trim($body['name'] ?? ''));
            $slug = preg_replace('/[^a-zа-я0-9]+/u', '-', $slug);
            $slug = trim($slug, '-');
        }

        $command = new Command(
            id: $categoryId,
            name: $body['name'] ?? '',
            slug: $slug,
        );

        try {
            $this->handler->handle($command);
            $this->em->flush();

            // Получаем обновленную категорию
            $category = $this->em->getRepository(\App\Modules\Entity\Category\Category::class)->find($categoryId);

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
