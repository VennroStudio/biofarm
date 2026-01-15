<?php

declare(strict_types=1);

namespace App\Modules\Query\Products\GetBySlug;

use App\Modules\Entity\Product\Product;
use App\Modules\Entity\Product\ProductRepository;
use DomainException;

final readonly class Fetcher
{
    public function __construct(
        private ProductRepository $productRepository,
    ) {}

    public function fetch(Query $query): Product
    {
        $product = $this->productRepository->findBySlug($query->slug);

        if (!$product) {
            throw new DomainException('Product not found');
        }

        return $product;
    }
}
