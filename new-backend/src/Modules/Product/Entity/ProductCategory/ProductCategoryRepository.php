<?php

declare(strict_types=1);

namespace App\Modules\Product\Entity\ProductCategory;

interface ProductCategoryRepository
{
    public function add(ProductCategory $category): void;

    public function remove(ProductCategory $category): void;

    public function getById(int $id): ProductCategory;

    public function findById(int $id): ?ProductCategory;

    public function findBySlug(string $slug): ?ProductCategory;
}
