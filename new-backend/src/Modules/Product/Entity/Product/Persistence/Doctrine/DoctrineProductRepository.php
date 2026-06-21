<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\Product\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Product\Entity\Product\Product;
use App\Modules\Product\Entity\Product\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineProductRepository implements ProductRepository
{
    /** @var EntityRepository<Product> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $this->em->getRepository(Product::class);
    }

    #[Override]
    public function add(Product $product): void
    {
        $this->em->persist($product);
    }

    #[Override]
    public function remove(Product $product): void
    {
        $this->em->remove($product);
    }

    #[Override]
    public function getById(int $id): Product
    {
        if (!$product = $this->findById($id)) {
            throw new DomainExceptionModule(
                module: 'product',
                message: 'error.product_not_found',
                code: 11,
            );
        }

        return $product;
    }

    #[Override]
    public function findById(int $id): ?Product
    {
        return $this->repo->findOneBy(['id' => $id, 'deletedAt' => null]);
    }

    #[Override]
    public function findBySlug(string $slug): ?Product
    {
        return $this->repo->findOneBy(['slug' => $slug, 'deletedAt' => null]);
    }

    #[Override]
    public function countByCategoryId(string $categoryId): int
    {
        return $this->repo->count([
            'categoryId' => $categoryId,
            'deletedAt'  => null,
        ]);
    }
}
