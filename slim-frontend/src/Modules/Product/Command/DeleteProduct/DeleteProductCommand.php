<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\DeleteProduct;

final readonly class DeleteProductCommand
{
    public function __construct(
        public int $id,
    ) {}
}
