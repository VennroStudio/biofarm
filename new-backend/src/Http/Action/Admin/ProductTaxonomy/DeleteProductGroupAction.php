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

final readonly class DeleteProductGroupAction implements RequestHandlerInterface
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

        $this->connection->update('product_groups', ['deleted_at' => $now, 'updated_at' => $now], ['id' => $id]);
        $this->connection->delete('product_group_items', ['group_id' => $id]);
        $this->cacher->deleteTag('products');

        return new JsonDataSuccessResponse(1, 200);
    }
}
