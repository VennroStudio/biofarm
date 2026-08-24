<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Faq;

use App\Components\Http\Response\JsonDataItemsResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetFaqItemsAction implements RequestHandlerInterface
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
                'id',
                'question',
                'answer',
                'page_scope',
                'page_id',
                'is_active',
                'sort_order',
                'created_at',
                'updated_at',
            )
            ->from('faq_items')
            ->orderBy('page_scope', 'ASC')
            ->addOrderBy('sort_order', 'ASC')
            ->addOrderBy('id', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        return new JsonDataItemsResponse(
            \count($items),
            array_map(static fn (array $row): array => [
                'id'         => (int)$row['id'],
                'question'   => (string)$row['question'],
                'answer'     => (string)$row['answer'],
                'page_scope' => (string)$row['page_scope'],
                'page_id'    => $row['page_id'] !== null ? (string)$row['page_id'] : null,
                'is_active'  => (bool)$row['is_active'],
                'sort_order' => (int)$row['sort_order'],
                'created_at' => (string)$row['created_at'],
                'updated_at' => $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
            ], $items),
        );
    }
}
