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

final readonly class DeleteFavoriteAction implements RequestHandlerInterface
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
        if ($productId <= 0) {
            throw new DomainExceptionModule('product', 'error.product_not_found', 1, status: 404);
        }

        $identity = RequestIdentity::get($request);
        $this->connection->delete('favorites', [
            'user_id'    => $identity->id,
            'product_id' => $productId,
        ]);

        return new JsonDataSuccessResponse(1, 200);
    }
}
