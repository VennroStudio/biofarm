<?php

declare(strict_types=1);

namespace App\Modules\Command\Product\Delete;

use App\Modules\Entity\Product\ProductRepository;
use Exception;

final readonly class Handler
{
    public function __construct(
        private ProductRepository $productRepository,
    ) {}

    public function handle(Command $command): void
    {
        $product = $this->productRepository->getById($command->productId);
        $this->productRepository->remove($product);
    }
}
