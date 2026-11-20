<?php

declare(strict_types=1);

namespace App\Modules\Review\Entity\Review\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Review\Entity\Review\Review;
use App\Modules\Review\Entity\Review\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineReviewRepository implements ReviewRepository
{
    /** @var EntityRepository<Review> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $this->em->getRepository(Review::class);
    }

    #[Override]
    public function add(Review $review): void
    {
        $this->em->persist($review);
    }

    #[Override]
    public function remove(Review $review): void
    {
        $this->em->remove($review);
    }

    #[Override]
    public function getById(string $id): Review
    {
        if (!$review = $this->findById($id)) {
            throw new DomainExceptionModule(
                module: 'review',
                message: 'error.review_not_found',
                code: 1,
            );
        }

        return $review;
    }

    #[Override]
    public function findById(string $id): ?Review
    {
        return $this->repo->findOneBy(['id' => $id, 'deletedAt' => null]);
    }
}
