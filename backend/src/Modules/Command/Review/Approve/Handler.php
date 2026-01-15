<?php

declare(strict_types=1);

namespace App\Modules\Command\Review\Approve;

use App\Modules\Entity\Review\Review;
use App\Modules\Entity\Review\ReviewRepository;

final readonly class Handler
{
    public function __construct(
        private ReviewRepository $reviewRepository,
    ) {}

    public function handle(Command $command): Review
    {
        $review = $this->reviewRepository->getById($command->reviewId);

        $review->approve();

        return $review;
    }
}
