<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Certificate;

use App\Components\Http\Response\JsonDataItemsResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetCertificatesAction implements RequestHandlerInterface
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
                'c.title',
                'c.file_path',
                'c.document_type',
                'c.product_id',
                'p.name AS product_name',
                'c.description',
                'c.is_active',
                'c.sort_order',
                'c.created_at',
                'c.updated_at',
            )
            ->from('certificates', 'c')
            ->leftJoin('c', 'products', 'p', 'p.id = c.product_id AND p.deleted_at IS NULL')
            ->orderBy('c.sort_order', 'ASC')
            ->addOrderBy('c.id', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        return new JsonDataItemsResponse(
            \count($items),
            array_map(static fn (array $row): array => [
                'id'            => (int)$row['id'],
                'title'         => (string)$row['title'],
                'file_path'     => (string)$row['file_path'],
                'document_type' => (string)$row['document_type'],
                'product_id'    => $row['product_id'] !== null ? (int)$row['product_id'] : null,
                'product_name'  => $row['product_name'] !== null ? (string)$row['product_name'] : null,
                'description'   => $row['description'] !== null ? (string)$row['description'] : null,
                'is_active'     => (bool)$row['is_active'],
                'sort_order'    => (int)$row['sort_order'],
                'created_at'    => (string)$row['created_at'],
                'updated_at'    => $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
            ], $items),
        );
    }
}
