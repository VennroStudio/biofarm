<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\ProductTaxonomy;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\String\SlugGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class SaveComponentAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $connection,
        private SlugGenerator $slugGenerator,
        private Cacher $cacher,
    ) {}

    /**
     * @throws Exception
     * @throws JsonException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->routeId($request);
        $payload = (array)$request->getParsedBody();
        $name = trim((string)($payload['name'] ?? ''));

        if ($name === '') {
            throw new DomainExceptionModule('product', 'error.component_name_required', 30);
        }

        $slug = $this->slug((string)($payload['slug'] ?? ''), $name);
        $this->assertSlugFree($slug, $id);

        $data = [
            'slug' => $slug,
            'name' => $name,
            'synonyms' => json_encode($this->stringList($payload['synonyms'] ?? []), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'short_description' => $this->nullableString($payload['short_description'] ?? null),
            'seo_title' => $this->nullableString($payload['seo_title'] ?? null),
            'seo_description' => $this->nullableString($payload['seo_description'] ?? null),
            'intro_text' => $this->nullableString($payload['intro_text'] ?? null),
            'is_indexable' => !isset($payload['is_indexable']) || (bool)$payload['is_indexable'] ? 1 : 0,
            'sort_order' => (int)($payload['sort_order'] ?? 0),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ];

        $isCreate = $id === null;
        if ($id !== null) {
            $this->connection->update('components', $data, ['id' => $id]);
        } else {
            $data['created_at'] = gmdate('Y-m-d H:i:s');
            $this->connection->insert('components', $data);
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

    private function slug(string $slug, string $name): string
    {
        return $this->slugGenerator->generate($slug !== '' ? $slug : $name);
    }

    /**
     * @throws Exception
     */
    private function assertSlugFree(string $slug, ?int $id): void
    {
        $existingId = $this->connection->fetchOne(
            'SELECT id FROM components WHERE slug = :slug AND deleted_at IS NULL LIMIT 1',
            ['slug' => $slug],
        );

        if ($existingId !== false && (int)$existingId !== $id) {
            throw new DomainExceptionModule('product', 'error.component_slug_already_exists', 31);
        }
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (\is_string($value)) {
            $value = preg_split('/\R/u', $value) ?: [];
        }

        if (!\is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $item): string => trim((string)$item), $value),
            static fn (string $item): bool => $item !== '',
        ));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string)$value);

        return $value !== '' ? $value : null;
    }
}
