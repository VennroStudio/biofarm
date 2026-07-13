<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\ProductTaxonomy;

use App\Components\Cacher\Cacher;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class DeleteAttributeAction implements RequestHandlerInterface
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
        $id = Route::getArgumentToInt($request, 'id');
        $now = gmdate('Y-m-d H:i:s');
        $valueIds = $this->connection->fetchFirstColumn(
            'SELECT id FROM attribute_values WHERE attribute_id = :id',
            ['id' => $id],
        );

        $this->connection->update('attributes', ['deleted_at' => $now, 'updated_at' => $now], ['id' => $id]);
        $this->connection->update('attribute_values', ['deleted_at' => $now, 'updated_at' => $now], ['attribute_id' => $id]);

        foreach ($valueIds as $valueId) {
            $this->connection->delete('product_attribute_values', ['attribute_value_id' => (int)$valueId]);
        }

        $this->cacher->deleteTag('products');

        return new JsonDataSuccessResponse(1, 200);
    }
}
