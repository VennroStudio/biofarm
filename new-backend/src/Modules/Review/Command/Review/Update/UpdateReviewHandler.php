<?php

declare(strict_types=1);

namespace App\Modules\Review\Command\Review\Update;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Modules\Review\Entity\Review\ReviewRepository;
use App\Modules\Review\Permission\ReviewPermission;
use App\Modules\Review\Service\ReviewPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;

final readonly class UpdateReviewHandler
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private ReviewPermissionService $permissionService,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function handle(UpdateReviewCommand $command): void
    {
        $this->permissionService->checkRole(UserRole::from($command->currentUserRole), ReviewPermission::UPDATE);

        $review = $this->reviewRepository->getById($command->reviewId);
        $oldProductId = $review->productId;

        $review->edit(
            productId: $command->productId,
            userName: trim($command->userName),
            rating: $command->rating,
            text: $command->text,
            source: $command->source,
            userId: $command->userId,
            images: $command->images,
        );

        $this->deleteCache($oldProductId);
        $this->deleteCache($command->productId);
        $this->cacher->delete('review_by_id_' . $command->reviewId);
        $this->flusher->flush();
    }

    private function deleteCache(int $productId): void
    {
        $this->cacher->delete('reviews_find_all_approved');
        $this->cacher->delete('reviews_find_all_any');
        $this->cacher->delete('reviews_by_product_' . $productId . '_approved');
        $this->cacher->delete('reviews_by_product_' . $productId . '_any');
    }
}
