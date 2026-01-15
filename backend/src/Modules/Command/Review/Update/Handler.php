<?php

declare(strict_types=1);

namespace App\Modules\Command\Review\Update;

use App\Modules\Entity\Review\Review;
use App\Modules\Entity\Review\ReviewRepository;
use Exception;

final readonly class Handler
{
    public function __construct(
        private ReviewRepository $repository,
    ) {}

    /** @throws Exception */
    public function handle(Command $command): Review
    {
        $review = $this->repository->getById($command->reviewId);

        $review->edit(
            productId: $command->productId,
            userName: $command->userName,
            rating: $command->rating,
            text: $command->text,
            source: $command->source,
            userId: $command->userId,
            images: $command->images,
        );

        return $review;
    }
}
