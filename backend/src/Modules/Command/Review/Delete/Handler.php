<?php

declare(strict_types=1);

namespace App\Modules\Command\Review\Delete;

use App\Modules\Entity\Review\ReviewRepository;

final readonly class Handler
{
    public function __construct(
        private ReviewRepository $reviewRepository,
    ) {}

    public function handle(Command $command): void
    {
        $review = $this->reviewRepository->getById($command->reviewId);

        $this->reviewRepository->remove($review);
    }
}
