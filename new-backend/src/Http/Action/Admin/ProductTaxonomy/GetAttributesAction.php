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

final readonly class GetAttributesAction implements RequestHandlerInterface
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
        $attributeRows = $this->connection->createQueryBuilder()
            ->select(
                'a.id',
                'a.slug',
                'a.name',
                'a.filter_prefix',
                'a.is_filterable',
                'a.is_indexable',
                'a.show_on_product',
                'a.sort_order',
                'COUNT(DISTINCT av.id) AS values_count',
                'COUNT(DISTINCT pav.product_id) AS products_count',
            )
            ->from('attributes', 'a')
            ->leftJoin('a', 'attribute_values', 'av', 'av.attribute_id = a.id AND av.deleted_at IS NULL')
            ->leftJoin('av', 'product_attribute_values', 'pav', 'pav.attribute_value_id = av.id')
            ->where('a.deleted_at IS NULL')
            ->groupBy('a.id')
            ->orderBy('a.sort_order', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $valueRows = $this->connection->createQueryBuilder()
            ->select(
                'av.id',
                'av.attribute_id',
                'av.slug',
                'av.name',
                'av.h1',
                'av.seo_title',
                'av.seo_description',
                'av.intro_text',
                'av.bottom_text',
                'av.short_description',
                'av.synonyms',
                'av.is_indexable',
                'av.sort_order',
                'COUNT(DISTINCT pav.product_id) AS products_count',
            )
            ->from('attribute_values', 'av')
            ->leftJoin('av', 'product_attribute_values', 'pav', 'pav.attribute_value_id = av.id')
            ->where('av.deleted_at IS NULL')
            ->groupBy('av.id')
            ->orderBy('av.sort_order', 'ASC')
            ->addOrderBy('av.name', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $valuesByAttribute = [];
        foreach ($valueRows as $row) {
            $valuesByAttribute[(int)$row['attribute_id']][] = self::mapValue($row);
        }

        $items = array_map(
            static fn (array $row): array => [
                'id'              => (int)$row['id'],
                'slug'            => (string)$row['slug'],
                'name'            => (string)$row['name'],
                'filter_prefix'   => $row['filter_prefix'] !== null ? (string)$row['filter_prefix'] : null,
                'is_filterable'   => (bool)(int)$row['is_filterable'],
                'is_indexable'    => (bool)(int)$row['is_indexable'],
                'show_on_product' => (bool)(int)$row['show_on_product'],
                'sort_order'      => (int)$row['sort_order'],
                'values_count'    => (int)$row['values_count'],
                'products_count'  => (int)$row['products_count'],
                'values'          => $valuesByAttribute[(int)$row['id']] ?? [],
            ],
            $attributeRows,
        );

        return new JsonDataItemsResponse(\count($items), $items);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function mapValue(array $row): array
    {
        return [
            'id'                => (int)$row['id'],
            'attribute_id'      => (int)$row['attribute_id'],
            'slug'              => (string)$row['slug'],
            'name'              => (string)$row['name'],
            'h1'                => $row['h1'],
            'seo_title'         => $row['seo_title'],
            'seo_description'   => $row['seo_description'],
            'intro_text'        => $row['intro_text'],
            'bottom_text'       => $row['bottom_text'],
            'short_description' => $row['short_description'],
            'synonyms'          => self::jsonList($row['synonyms']),
            'is_indexable'      => (bool)(int)$row['is_indexable'],
            'sort_order'        => (int)$row['sort_order'],
            'products_count'    => (int)$row['products_count'],
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
