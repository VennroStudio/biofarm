<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Review;

use App\Components\Http\Response\JsonDataItemsResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Http\Unifier\Review\ReviewUnifier;
use App\Modules\Review\Query\Review\FindAll\ReviewFindAllFetcher;
use App\Modules\Review\Query\Review\FindAll\ReviewFindAllQuery;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Get(path: '/reviews', summary: 'Список отзывов', tags: ['Reviews'])]
final readonly class GetReviewsAction implements RequestHandlerInterface
{
    public function __construct(
        private ReviewFindAllFetcher $fetcher,
        private ReviewUnifier $unifier,
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
        $params = $request->getQueryParams();
        $query = $this->denormalizer->denormalize($params, ReviewFindAllQuery::class);

        if (isset($params['all']) || (($params['onlyApproved'] ?? null) === 'false')) {
            $query->onlyApproved = false;
        }

        $this->validator->validate($query);
        $result = $this->fetcher->fetch($query);

        return new JsonDataItemsResponse(
            count: $result->count,
            items: $this->unifier->unify(null, $result->items),
        );
    }
}
