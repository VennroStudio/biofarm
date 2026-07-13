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

final readonly class SaveAttributeValueAction implements RequestHandlerInterface
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
        $id = $this->routeInt($request, 'id');
        $attributeId = $id !== null
            ? $this->attributeIdByValueId($id)
            : $this->routeInt($request, 'attributeId');
        $payload = (array)$request->getParsedBody();
        $name = trim((string)($payload['name'] ?? ''));

        if ($attributeId === null || $attributeId <= 0) {
            throw new DomainExceptionModule('product', 'error.attribute_required', 37);
        }

        if ($name === '') {
            throw new DomainExceptionModule('product', 'error.attribute_value_name_required', 38);
        }

        $this->assertAttributeExists($attributeId);
        $slug = $this->slugGenerator->generate(trim((string)($payload['slug'] ?? '')) ?: $name);
        $this->assertSlugFree($attributeId, $slug, $id);

        $data = [
            'attribute_id'      => $attributeId,
            'slug'              => $slug,
            'name'              => $name,
            'h1'                => $this->nullableString($payload['h1'] ?? null),
            'seo_title'         => $this->nullableString($payload['seo_title'] ?? null),
            'seo_description'   => $this->nullableString($payload['seo_description'] ?? null),
            'intro_text'        => $this->nullableString($payload['intro_text'] ?? null),
            'bottom_text'       => $this->nullableString($payload['bottom_text'] ?? null),
            'short_description' => $this->nullableString($payload['short_description'] ?? null),
            'synonyms'          => json_encode($this->stringList($payload['synonyms'] ?? []), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'is_indexable'      => !isset($payload['is_indexable']) || (bool)$payload['is_indexable'] ? 1 : 0,
            'sort_order'        => (int)($payload['sort_order'] ?? 0),
            'updated_at'        => gmdate('Y-m-d H:i:s'),
        ];

        $isCreate = $id === null;
        if ($id !== null) {
            $this->connection->update('attribute_values', $data, ['id' => $id]);
        } else {
            $data['created_at'] = gmdate('Y-m-d H:i:s');
            $this->connection->insert('attribute_values', $data);
            $id = (int)$this->connection->lastInsertId();
        }

        $this->cacher->deleteTag('products');

        return new JsonDataResponse(['id' => $id], $isCreate ? 201 : 200);
    }

    private function routeInt(ServerRequestInterface $request, string $name): ?int
    {
        $argument = RouteContext::fromRequest($request)
            ->getRoute()
            ?->getArgument($name);

        return $argument !== null ? (int)$argument : null;
    }

    /**
     * @throws Exception
     */
    private function attributeIdByValueId(int $id): int
    {
        $attributeId = $this->connection->fetchOne(
            'SELECT attribute_id FROM attribute_values WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => $id],
        );

        if ($attributeId === false) {
            throw new DomainExceptionModule('product', 'error.attribute_value_not_found', 39);
        }

        return (int)$attributeId;
    }

    /**
     * @throws Exception
     */
    private function assertAttributeExists(int $attributeId): void
    {
        $exists = $this->connection->fetchOne(
            'SELECT id FROM attributes WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => $attributeId],
        );

        if ($exists === false) {
            throw new DomainExceptionModule('product', 'error.attribute_not_found', 40);
        }
    }

    /**
     * @throws Exception
     */
    private function assertSlugFree(int $attributeId, string $slug, ?int $id): void
    {
        $existingId = $this->connection->fetchOne(
            'SELECT id FROM attribute_values WHERE attribute_id = :attributeId AND slug = :slug AND deleted_at IS NULL LIMIT 1',
            ['attributeId' => $attributeId, 'slug' => $slug],
        );

        if ($existingId !== false && (int)$existingId !== $id) {
            throw new DomainExceptionModule('product', 'error.attribute_value_slug_already_exists', 41);
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
