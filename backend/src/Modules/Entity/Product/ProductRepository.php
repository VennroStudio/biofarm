<?php

declare(strict_types=1);

namespace App\Modules\Entity\Product;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class ProductRepository
{
    /** @var EntityRepository<Product> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(Product::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): Product
    {
        if (!$product = $this->findById($id)) {
            throw new Exception('Product Not Found');
        }

        return $product;
    }

    public function findById(int $id): ?Product
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->repo->findOneBy(['slug' => $slug, 'isActive' => true]);
    }

    /** @return Product[] */
    public function findByCategory(string $categoryId): array
    {
        if ($categoryId === 'all') {
            return $this->repo->findBy(['isActive' => true], ['createdAt' => 'DESC']);
        }
        return $this->repo->findBy(['categoryId' => $categoryId, 'isActive' => true], ['createdAt' => 'DESC']);
    }

    /** @return Product[] */
    public function findAllActive(): array
    {
        return $this->repo->findBy(['isActive' => true], ['createdAt' => 'DESC']);
    }

    /** @return Product[] */
    public function findAll(): array
    {
        return $this->repo->findBy([], ['createdAt' => 'DESC']);
    }

    public function add(Product $product): void
    {
        $this->em->persist($product);
    }

    public function remove(Product $product): void
    {
        $this->em->remove($product);
    }
}
