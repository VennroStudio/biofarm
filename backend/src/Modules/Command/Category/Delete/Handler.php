<?php

declare(strict_types=1);

namespace App\Modules\Command\Category\Delete;

use App\Modules\Entity\Category\CategoryRepository;
use App\Modules\Entity\Product\ProductRepository;
use DomainException;

final readonly class Handler
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
    ) {}

    public function handle(Command $command): void
    {
        $category = $this->categoryRepository->findById($command->categoryId);

        if (!$category) {
            throw new DomainException('Category not found');
        }

        // Проверяем, есть ли товары в этой категории
        $products = $this->productRepository->findByCategory((string)$category->getId());
        if (count($products) > 0) {
            throw new DomainException('Cannot delete category with products');
        }

        $this->categoryRepository->remove($category);
    }
}
