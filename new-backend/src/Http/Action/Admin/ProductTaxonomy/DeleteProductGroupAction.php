<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\ProductTaxonomy;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

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

        $this->connection->beginTransaction();
        try {
            $affected = $this->connection->createQueryBuilder()
                ->update('product_groups')
                ->set('deleted_at', ':deletedAt')
                ->set('updated_at', ':updatedAt')
                ->where('id = :id')
                ->andWhere('deleted_at IS NULL')
                ->setParameter('deletedAt', $now)
                ->setParameter('updatedAt', $now)
                ->setParameter('id', $id)
                ->executeStatement();
            if ($affected === 0) {
                throw new DomainExceptionModule('product', 'error.product_group_not_found', 43, status: 404);
            }

            $this->connection->delete('product_group_items', ['group_id' => $id]);
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }

        $this->cacher->deleteTag('products');

        return new JsonDataSuccessResponse(1, 200);
    }
}
