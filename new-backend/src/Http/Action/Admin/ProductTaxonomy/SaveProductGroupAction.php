<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\ProductTaxonomy;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataResponse;
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
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->routeId($request);
        $payload = (array)$request->getParsedBody();
        $name = trim((string)($payload['name'] ?? ''));

        if ($name === '') {
            throw new DomainExceptionModule('product', 'error.product_group_name_required', 42);
        }

        $data = [
            'name'       => $name,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ];

        $isCreate = $id === null;
        if ($id !== null) {
            $this->connection->update('product_groups', $data, ['id' => $id]);
        } else {
            $data['created_at'] = gmdate('Y-m-d H:i:s');
            $this->connection->insert('product_groups', $data);
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
}
