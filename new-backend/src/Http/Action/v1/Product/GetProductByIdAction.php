<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Product;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Http\Unifier\Product\ProductUnifier;
use App\Modules\Product\Query\Product\GetById\ProductGetByIdFetcher;
use App\Modules\Product\Query\Product\GetById\ProductGetByIdQuery;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OA\Get(path: '/products/{id}', summary: 'Товар по ID', tags: ['Product'])]
final readonly class GetProductByIdAction implements RequestHandlerInterface
{
    public function __construct(
        private ProductGetByIdFetcher $fetcher,
        private ProductUnifier $unifier,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $product = $this->fetcher->fetch(
            new ProductGetByIdQuery(Route::getArgumentToInt($request, 'id'))
        );

        return new JsonDataResponse(
            $this->unifier->unifyOne(null, $product),
        );
    }
}
