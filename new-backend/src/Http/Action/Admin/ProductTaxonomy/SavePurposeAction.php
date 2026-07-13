<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\ProductTaxonomy;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\String\SlugGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class SavePurposeAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $connection,
        private SlugGenerator $slugGenerator,
        private Cacher $cacher,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->routeId($request);
        $payload = (array)$request->getParsedBody();
        $name = trim((string)($payload['name'] ?? ''));

        if ($name === '') {
            throw new DomainExceptionModule('product', 'error.purpose_name_required', 32);
        }

        $slug = $this->slugGenerator->generate(trim((string)($payload['slug'] ?? '')) ?: $name);
        $this->assertSlugFree($slug, $id);

        $data = [
            'slug' => $slug,
            'name' => $name,
            'h1' => $this->nullableString($payload['h1'] ?? null),
            'seo_title' => $this->nullableString($payload['seo_title'] ?? null),
            'seo_description' => $this->nullableString($payload['seo_description'] ?? null),
            'intro_text' => $this->nullableString($payload['intro_text'] ?? null),
            'bottom_text' => $this->nullableString($payload['bottom_text'] ?? null),
            'is_indexable' => !isset($payload['is_indexable']) || (bool)$payload['is_indexable'] ? 1 : 0,
            'sort_order' => (int)($payload['sort_order'] ?? 0),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ];

        $isCreate = $id === null;
        if ($id !== null) {
            $this->connection->update('product_purposes', $data, ['id' => $id]);
        } else {
            $data['created_at'] = gmdate('Y-m-d H:i:s');
            $this->connection->insert('product_purposes', $data);
            $id = (int)$this->connection->lastInsertId();
        }

        $this->cacher->deleteTag('products');

        return new JsonDataResponse(['id' => $id], $isCreate ? 201 : 200);
    }

    private function routeId(ServerRequestInterface $request): ?int
    {
        $argument = RouteContext::fromRequest($request)
            ->getRoute()
            ?->getArgument('id');

        return $argument !== null ? (int)$argument : null;
    }

    /**
     * @throws Exception
     */
    private function assertSlugFree(string $slug, ?int $id): void
    {
        $existingId = $this->connection->fetchOne(
            'SELECT id FROM product_purposes WHERE slug = :slug AND deleted_at IS NULL LIMIT 1',
            ['slug' => $slug],
        );

        if ($existingId !== false && (int)$existingId !== $id) {
            throw new DomainExceptionModule('product', 'error.purpose_slug_already_exists', 33);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string)$value);

        return $value !== '' ? $value : null;
    }
}
