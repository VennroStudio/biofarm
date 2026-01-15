<?php

declare(strict_types=1);

namespace App\Modules\Query\Reviews\GetByProductId;

use App\Modules\Entity\Review\Review;
use App\Modules\Entity\Review\ReviewRepository;

final readonly class Fetcher
{
    public function __construct(
        private ReviewRepository $reviewRepository,
    ) {}

    /** @return Review[] */
    public function fetch(Query $query): array
    {
        return $this->reviewRepository->findByProductId($query->productId, $query->onlyApproved);
    }
}
