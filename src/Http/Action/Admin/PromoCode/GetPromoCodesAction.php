<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\PromoCode;

use App\Components\Http\Response\JsonDataItemsResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetPromoCodesAction implements RequestHandlerInterface
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
                'code',
                'type',
                'value',
                'min_order_total',
                'starts_at',
                'ends_at',
                'usage_limit',
                'used_count',
                'is_active',
                'created_at',
                'updated_at',
            )
            ->from('promo_codes')
            ->orderBy('created_at', 'DESC')
            ->addOrderBy('id', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        return new JsonDataItemsResponse(
            \count($items),
            array_map(static fn (array $row): array => [
                'id'              => (int)$row['id'],
                'code'            => (string)$row['code'],
                'type'            => (string)$row['type'],
                'value'           => (int)$row['value'],
                'min_order_total' => (int)$row['min_order_total'],
                'starts_at'       => $row['starts_at'] !== null ? (string)$row['starts_at'] : null,
                'ends_at'         => $row['ends_at'] !== null ? (string)$row['ends_at'] : null,
                'usage_limit'     => $row['usage_limit'] !== null ? (int)$row['usage_limit'] : null,
                'used_count'      => (int)$row['used_count'],
                'is_active'       => (bool)$row['is_active'],
                'created_at'      => (string)$row['created_at'],
                'updated_at'      => $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
            ], $items),
        );
    }
}
