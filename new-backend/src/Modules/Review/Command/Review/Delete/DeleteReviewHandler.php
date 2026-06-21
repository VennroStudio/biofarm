<?php

declare(strict_types=1);

namespace App\Modules\Review\Command\Review\Delete;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Modules\Review\Entity\Review\ReviewRepository;
use App\Modules\Review\Permission\ReviewPermission;
use App\Modules\Review\Service\ReviewPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;

final readonly class DeleteReviewHandler
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
    public function handle(DeleteReviewCommand $command): void
    {
        $this->permissionService->checkRole(UserRole::from($command->currentUserRole), ReviewPermission::DELETE);

        $review = $this->reviewRepository->getById($command->reviewId);
        $review->markDeleted();

        $this->cacher->delete('reviews_find_all_approved');
        $this->cacher->delete('reviews_find_all_any');
        $this->cacher->delete('reviews_by_product_' . $review->productId . '_approved');
        $this->cacher->delete('reviews_by_product_' . $review->productId . '_any');
        $this->cacher->delete('review_by_id_' . $command->reviewId);
        $this->flusher->flush();
    }
}
