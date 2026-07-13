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

final readonly class GetPurposesAction implements RequestHandlerInterface
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
                'p.id',
                'p.slug',
                'p.name',
                'p.h1',
                'p.seo_title',
                'p.seo_description',
                'p.intro_text',
                'p.bottom_text',
                'p.is_indexable',
                'p.sort_order',
                'COUNT(ppr.id) AS products_count',
            )
            ->from('product_purposes', 'p')
            ->leftJoin('p', 'product_purpose_relations', 'ppr', 'ppr.purpose_id = p.id')
            ->where('p.deleted_at IS NULL')
            ->groupBy('p.id')
            ->orderBy('p.sort_order', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return new JsonDataItemsResponse(
            count: \count($items),
            items: array_map(static fn (array $row): array => [
                'id' => (int)$row['id'],
                'slug' => (string)$row['slug'],
                'name' => (string)$row['name'],
                'h1' => $row['h1'],
                'seo_title' => $row['seo_title'],
                'seo_description' => $row['seo_description'],
                'intro_text' => $row['intro_text'],
                'bottom_text' => $row['bottom_text'],
                'is_indexable' => (bool)(int)$row['is_indexable'],
                'sort_order' => (int)$row['sort_order'],
                'products_count' => (int)$row['products_count'],
            ], $items),
        );
    }
}
