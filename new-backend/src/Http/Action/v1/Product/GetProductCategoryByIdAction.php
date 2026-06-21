<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Product;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Http\Unifier\Product\ProductCategoryUnifier;
use App\Modules\Product\Query\ProductCategory\GetById\ProductCategoryGetByIdFetcher;
use App\Modules\Product\Query\ProductCategory\GetById\ProductCategoryGetByIdQuery;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OA\Get(path: '/product-categories/{id}', summary: 'Категория товара по ID', tags: ['Product'])]
final readonly class GetProductCategoryByIdAction implements RequestHandlerInterface
{
    public function __construct(
        private ProductCategoryGetByIdFetcher $fetcher,
        private ProductCategoryUnifier $unifier,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $category = $this->fetcher->fetch(
            new ProductCategoryGetByIdQuery(Route::getArgumentToInt($request, 'id'))
        );

        return new JsonDataResponse(
            $this->unifier->unifyOne(null, $category),
        );
    }
}
