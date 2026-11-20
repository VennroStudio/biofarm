<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\Product;

interface ProductRepository
{
    public function add(Product $product): void;

    public function remove(Product $product): void;

    public function getById(int $id): Product;

    public function findById(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function countByCategoryId(string $categoryId): int;
}
