<?php

declare(strict_types=1);

namespace App\Modules\Entity\Review;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class ReviewRepository
{
    /** @var EntityRepository<Review> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(Review::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(string $id): Review
    {
        if (!$review = $this->findById($id)) {
            throw new Exception('Review Not Found');
        }

        return $review;
    }

    public function findById(string $id): ?Review
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    /** @return Review[] */
    public function findByProductId(int $productId, bool $onlyApproved = true): array
    {
        $criteria = ['productId' => $productId];
        if ($onlyApproved) {
            $criteria['isApproved'] = true;
        }
        return $this->repo->findBy($criteria, ['createdAt' => 'DESC']);
    }

    /** @return Review[] */
    public function findAll(bool $onlyApproved = false): array
    {
        $criteria = [];
        if ($onlyApproved) {
            $criteria['isApproved'] = true;
        }
        return $this->repo->findBy($criteria, ['createdAt' => 'DESC']);
    }

    public function add(Review $review): void
    {
        $this->em->persist($review);
    }

    public function remove(Review $review): void
    {
        $this->em->remove($review);
    }
}
