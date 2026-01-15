<?php

declare(strict_types=1);

namespace App\Modules\Command\Review\Create;

use App\Modules\Entity\Review\Review;
use App\Modules\Entity\Review\ReviewRepository;

final readonly class Handler
{
    public function __construct(
        private ReviewRepository $reviewRepository,
    ) {}

    public function handle(Command $command): Review
    {
        $review = Review::create(
            id: $command->reviewId,
            productId: $command->productId,
            userName: $command->userName,
            rating: $command->rating,
            text: $command->text,
            source: $command->source,
            userId: $command->userId,
            images: $command->images,
            isApproved: $command->isApproved,
        );

        $this->reviewRepository->add($review);

        return $review;
    }
}
