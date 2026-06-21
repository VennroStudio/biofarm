<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Product;

use App\Components\Http\Response\JsonDataItemsResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Http\Unifier\Product\ProductUnifier;
use App\Modules\Product\Query\Product\FindAll\ProductFindAllFetcher;
use App\Modules\Product\Query\Product\FindAll\ProductFindAllQuery;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Get(path: '/products', summary: 'Список товаров', tags: ['Product'])]
final readonly class GetProductsAction implements RequestHandlerInterface
{
    public function __construct(
        private ProductFindAllFetcher $fetcher,
        private ProductUnifier $unifier,
        private Denormalizer $denormalizer,
        private Validator $validator,
    ) {}

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $this->denormalizer->denormalize(
            $request->getQueryParams(),
            ProductFindAllQuery::class,
        );

        $this->validator->validate($query);
        $result = $this->fetcher->fetch($query);

        return new JsonDataItemsResponse(
            count: $result->count,
            items: $this->unifier->unify(null, $result->items),
        );
    }
}
