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

final readonly class SaveAttributeAction implements RequestHandlerInterface
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
            throw new DomainExceptionModule('product', 'error.attribute_name_required', 34);
        }

        $slug = $this->slugGenerator->generate(trim((string)($payload['slug'] ?? '')) ?: $name);
        $filterPrefix = $this->nullableSlug($payload['filter_prefix'] ?? null);
        $this->assertSlugFree($slug, $id);
        $this->assertFilterPrefixFree($filterPrefix, $id);

        $data = [
            'slug'            => $slug,
            'name'            => $name,
            'filter_prefix'   => $filterPrefix,
            'is_filterable'   => !isset($payload['is_filterable']) || (bool)$payload['is_filterable'] ? 1 : 0,
            'is_indexable'    => !isset($payload['is_indexable']) || (bool)$payload['is_indexable'] ? 1 : 0,
            'show_on_product' => !isset($payload['show_on_product']) || (bool)$payload['show_on_product'] ? 1 : 0,
            'sort_order'      => (int)($payload['sort_order'] ?? 0),
            'updated_at'      => gmdate('Y-m-d H:i:s'),
        ];

        $isCreate = $id === null;
        if ($id !== null) {
            $this->connection->update('attributes', $data, ['id' => $id]);
        } else {
            $data['created_at'] = gmdate('Y-m-d H:i:s');
            $this->connection->insert('attributes', $data);
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
            'SELECT id FROM attributes WHERE slug = :slug AND deleted_at IS NULL LIMIT 1',
            ['slug' => $slug],
        );

        if ($existingId !== false && (int)$existingId !== $id) {
            throw new DomainExceptionModule('product', 'error.attribute_slug_already_exists', 35);
        }
    }

    /**
     * @throws Exception
     */
    private function assertFilterPrefixFree(?string $filterPrefix, ?int $id): void
    {
        if ($filterPrefix === null) {
            return;
        }

        $existingId = $this->connection->fetchOne(
            'SELECT id FROM attributes WHERE filter_prefix = :filterPrefix AND deleted_at IS NULL LIMIT 1',
            ['filterPrefix' => $filterPrefix],
        );

        if ($existingId !== false && (int)$existingId !== $id) {
            throw new DomainExceptionModule('product', 'error.attribute_filter_prefix_already_exists', 36);
        }
    }

    private function nullableSlug(mixed $value): ?string
    {
        $value = trim((string)$value);

        return $value !== '' ? $this->slugGenerator->generate($value) : null;
    }
}
