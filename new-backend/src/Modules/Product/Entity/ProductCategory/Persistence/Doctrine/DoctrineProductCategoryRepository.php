<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductCategory\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Product\Entity\ProductCategory\ProductCategory;
use App\Modules\Product\Entity\ProductCategory\ProductCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineProductCategoryRepository implements ProductCategoryRepository
{
    /** @var EntityRepository<ProductCategory> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $this->em->getRepository(ProductCategory::class);
    }

    #[Override]
    public function add(ProductCategory $category): void
    {
        $this->em->persist($category);
    }

    #[Override]
    public function remove(ProductCategory $category): void
    {
        $this->em->remove($category);
    }

    #[Override]
    public function getById(int $id): ProductCategory
    {
        if (!$category = $this->findById($id)) {
            throw new DomainExceptionModule(
                module: 'product',
                message: 'error.category_not_found',
                code: 1,
            );
        }

        return $category;
    }

    #[Override]
    public function findById(int $id): ?ProductCategory
    {
        return $this->repo->findOneBy(['id' => $id, 'deletedAt' => null]);
    }

    #[Override]
    public function findBySlug(string $slug): ?ProductCategory
    {
        return $this->repo->findOneBy(['slug' => $slug, 'deletedAt' => null]);
    }
}
