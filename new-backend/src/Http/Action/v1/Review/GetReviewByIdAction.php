<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Review;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Http\Unifier\Review\ReviewUnifier;
use App\Modules\Review\Query\Review\GetById\ReviewGetByIdFetcher;
use App\Modules\Review\Query\Review\GetById\ReviewGetByIdQuery;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OA\Get(path: '/reviews/{id}', summary: 'Отзыв по ID', tags: ['Reviews'])]
final readonly class GetReviewByIdAction implements RequestHandlerInterface
{
    public function __construct(
        private ReviewGetByIdFetcher $fetcher,
        private ReviewUnifier $unifier,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $review = $this->fetcher->fetch(new ReviewGetByIdQuery(Route::getArgument($request, 'id')));

        return new JsonDataResponse($this->unifier->unifyOne(null, $review));
    }
}
