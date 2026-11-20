<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\ProductTaxonomy;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataResponse;
use App\Modules\Product\Service\ProductGroupMembers;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class SaveProductGroupAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $connection,
        private Cacher $cacher,
        private ProductGroupMembers $members,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->routeId($request);
        $payload = (array)$request->getParsedBody();
        $name = is_string($payload['name'] ?? null) ? trim($payload['name']) : '';

        if ($name === '') {
            throw new DomainExceptionModule('product', 'error.product_group_name_required', 42, status: 422);
        }
        if (mb_strlen($name) > 255) {
            throw new DomainExceptionModule('product', 'error.product_group_name_too_long', 48, status: 422);
        }

        $hasProducts = array_key_exists('product_ids', $payload);
        $productIds = $hasProducts ? $this->productIds($payload['product_ids']) : null;
        $mainId = $payload['main_product_id'] ?? null;
        if ($mainId !== null && (!$hasProducts || !is_int($mainId) || $mainId <= 0)) {
            throw new DomainExceptionModule('product', 'error.product_group_main_not_member', 47, status: 422);
        }

        $isCreate = $id === null;
        $id = $this->connection->transactional(function () use ($id, $payload, $name, $productIds, $mainId): int {
            $data = ['name' => $name, 'updated_at' => gmdate('Y-m-d H:i:s')];
            if ($id === null) {
                $this->connection->insert('product_groups', $data + ['created_at' => $data['updated_at']]);
                $id = (int) $this->connection->lastInsertId();
            } else {
                $group = $this->members->details($id, lock: true);
                if ($productIds !== null && ($payload['revision'] ?? null) !== $group['revision']) {
                    throw new DomainExceptionModule('product', 'error.product_group_edit_conflict', 46, status: 409);
                }
                $this->connection->update('product_groups', $data, ['id' => $id]);
            }

            if ($productIds !== null) {
                $this->members->replace($id, $productIds, $mainId);
            }
            return $id;
        });

        $this->cacher->deleteTag('products');

        return new JsonDataResponse(['id' => $id], $isCreate ? 201 : 200);
    }

    /** @return list<int> */
    private function productIds(mixed $items): array
    {
        if (!is_array($items) || !array_is_list($items)) {
            throw new DomainExceptionModule('product', 'error.product_group_products_unavailable', 45, status: 422);
        }
        foreach ($items as $id) {
            if (!is_int($id) || $id <= 0) {
                throw new DomainExceptionModule('product', 'error.product_group_products_unavailable', 45, status: 422);
            }
        }
        return array_values(array_unique($items));
    }

    private function routeId(ServerRequestInterface $request): ?int
    {
        $argument = RouteContext::fromRequest($request)
            ->getRoute()
            ?->getArgument('id');

        return $argument !== null ? (int)$argument : null;
    }
}
