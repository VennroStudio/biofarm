<?php

declare(strict_types=1);

namespace App\Modules\Query\Products\GetAll;

use App\Modules\Entity\Product\Product;
use App\Modules\Entity\Product\ProductRepository;

final readonly class Fetcher
{
    public function __construct(
        private ProductRepository $productRepository,
    ) {}

    /** @return Product[] */
    public function fetch(Query $query): array
    {
        // Если includeInactive = true, возвращаем все товары (для админки)
        if ($query->includeInactive) {
            return $this->productRepository->findAll();
        }

        if ($query->categoryId) {
            return $this->productRepository->findByCategory($query->categoryId);
        }

        return $this->productRepository->findAllActive();
    }
}
