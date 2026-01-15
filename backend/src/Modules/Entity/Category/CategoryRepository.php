<?php

declare(strict_types=1);

namespace App\Modules\Entity\Category;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class CategoryRepository
{
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $this->em->getRepository(Category::class);
    }

    public function add(Category $category): void
    {
        $this->em->persist($category);
    }

    public function remove(Category $category): void
    {
        $this->em->remove($category);
    }

    public function findById(int $id): ?Category
    {
        return $this->repo->find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->repo->findOneBy(['slug' => $slug]);
    }

    public function findAll(): array
    {
        return $this->repo->findAll();
    }

    public function findActive(): array
    {
        return $this->repo->findBy([], ['name' => 'ASC']);
    }
}
