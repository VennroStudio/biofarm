<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Review;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Modules\Review\Entity\Review\ReviewRepository;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ApproveReviewAction implements RequestHandlerInterface
{
    public function __construct(
        private ReviewRepository $repository,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $review = $this->repository->getById(Route::getArgument($request, 'id'));
        $review->approve();

        $this->cacher->delete('review_by_id_' . $review->id);
        $this->cacher->delete('reviews_find_all_approved');
        $this->cacher->delete('reviews_find_all_any');
        $this->cacher->delete('reviews_by_product_' . $review->productId . '_approved');
        $this->cacher->delete('reviews_by_product_' . $review->productId . '_any');
        $this->flusher->flush();

        return new JsonDataSuccessResponse(1, 200);
    }
}
