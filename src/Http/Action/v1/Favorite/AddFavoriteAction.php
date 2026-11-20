<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Favorite;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Components\Setting\SiteSettings;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class AddFavoriteAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $connection,
        private SiteSettings $settings,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->settings->bool('favorites_enabled')) {
            throw new DomainExceptionModule('user', 'error.favorites_disabled', 35, status: 403);
        }

        $productId = Route::getArgumentToInt($request, 'productId');
        if ($productId <= 0 || !$this->productExists($productId)) {
            throw new DomainExceptionModule('product', 'error.product_not_found', 1, status: 404);
        }

        $identity = RequestIdentity::get($request);
        $this->connection->executeStatement(
            'INSERT IGNORE INTO favorites (user_id, product_id, created_at) VALUES (:userId, :productId, UTC_TIMESTAMP())',
            [
                'userId'    => $identity->id,
                'productId' => $productId,
            ],
        );

        return new JsonDataSuccessResponse(1, 200);
    }

    /**
     * @throws Exception
     */
    private function productExists(int $productId): bool
    {
        return (bool)$this->connection->createQueryBuilder()
            ->select('p.id')
            ->from('products', 'p')
            ->where('p.id = :id')
            ->andWhere('p.deleted_at IS NULL')
            ->andWhere('p.is_active = 1')
            ->setParameter('id', $productId)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }
}
