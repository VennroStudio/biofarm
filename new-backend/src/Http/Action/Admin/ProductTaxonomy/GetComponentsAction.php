<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\ProductTaxonomy;

use App\Components\Http\Response\JsonDataItemsResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetComponentsAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $items = $this->connection->createQueryBuilder()
            ->select(
                'c.id',
                'c.slug',
                'c.name',
                'c.synonyms',
                'c.short_description',
                'c.seo_title',
                'c.seo_description',
                'c.intro_text',
                'c.is_indexable',
                'c.sort_order',
                'COUNT(pc.id) AS products_count',
            )
            ->from('components', 'c')
            ->leftJoin('c', 'product_components', 'pc', 'pc.component_id = c.id')
            ->where('c.deleted_at IS NULL')
            ->groupBy('c.id')
            ->orderBy('c.sort_order', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return new JsonDataItemsResponse(
            count: \count($items),
            items: array_map(self::map(...), $items),
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function map(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'slug' => (string)$row['slug'],
            'name' => (string)$row['name'],
            'synonyms' => self::jsonList($row['synonyms']),
            'short_description' => $row['short_description'],
            'seo_title' => $row['seo_title'],
            'seo_description' => $row['seo_description'],
            'intro_text' => $row['intro_text'],
            'is_indexable' => (bool)(int)$row['is_indexable'],
            'sort_order' => (int)$row['sort_order'],
            'products_count' => (int)$row['products_count'],
        ];
    }

    /**
     * @return list<string>
     */
    private static function jsonList(mixed $value): array
    {
        if (\is_array($value)) {
            return array_values(array_filter($value, static fn (mixed $item): bool => \is_string($item) && trim($item) !== ''));
        }

        $decoded = json_decode((string)$value, true);

        return \is_array($decoded)
            ? array_values(array_filter($decoded, static fn (mixed $item): bool => \is_string($item) && trim($item) !== ''))
            : [];
    }
}
