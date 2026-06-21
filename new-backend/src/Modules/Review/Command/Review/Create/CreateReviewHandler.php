<?php

declare(strict_types=1);

namespace App\Modules\Review\Command\Review\Create;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Components\Id\ReadableIdGenerator;
use App\Modules\Review\Entity\Review\Review;
use App\Modules\Review\Entity\Review\ReviewRepository;
use App\Modules\Review\Permission\ReviewPermission;
use App\Modules\Review\Service\ReviewPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;
use Random\RandomException;

final readonly class CreateReviewHandler
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private ReadableIdGenerator $idGenerator,
        private ReviewPermissionService $permissionService,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    public function handle(CreateReviewCommand $command): string
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: ReviewPermission::CREATE,
        );

        $id = $command->reviewId ?? $this->idGenerator->generate('rev');

        $review = Review::create(
            id: $id,
            productId: $command->productId,
            userName: trim($command->userName),
            rating: $command->rating,
            text: $command->text,
            source: $command->source,
            userId: $command->userId,
            images: $command->images,
            isApproved: $command->isApproved,
        );

        $this->reviewRepository->add($review);
        $this->deleteCache($command->productId);
        $this->flusher->flush();

        return $id;
    }

    private function deleteCache(int $productId): void
    {
        $this->cacher->delete('reviews_find_all_approved');
        $this->cacher->delete('reviews_find_all_any');
        $this->cacher->delete('reviews_by_product_' . $productId . '_approved');
        $this->cacher->delete('reviews_by_product_' . $productId . '_any');
    }
}
