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

final readonly class GetProductGroupsAction implements RequestHandlerInterface
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
            ->select('g.id', 'g.name', 'COUNT(gi.id) AS products_count')
            ->from('product_groups', 'g')
            ->leftJoin('g', 'product_group_items', 'gi', 'gi.group_id = g.id')
            ->where('g.deleted_at IS NULL')
            ->groupBy('g.id')
            ->orderBy('g.name', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return new JsonDataItemsResponse(
            \count($items),
            array_map(static fn (array $row): array => [
                'id'             => (int)$row['id'],
                'name'           => (string)$row['name'],
                'products_count' => (int)$row['products_count'],
            ], $items),
        );
    }
}
