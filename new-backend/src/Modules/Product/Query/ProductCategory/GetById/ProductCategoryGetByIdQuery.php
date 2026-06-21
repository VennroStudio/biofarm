<?php

declare(strict_types=1);

namespace App\Modules\Product\Query\ProductCategory\GetById;

final readonly class ProductCategoryGetByIdQuery
{
    public function __construct(
        public int $id,
    ) {}
}
