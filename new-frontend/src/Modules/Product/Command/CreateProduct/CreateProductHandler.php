<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\CreateProduct;

use App\Modules\Product\Api\ProductApi;
use App\Modules\Product\Api\Response\ProductResponse;

final readonly class CreateProductHandler
{
    public function __construct(
        private ProductApi $api,
    ) {}

    public function handle(CreateProductCommand $command): ProductResponse
    {
        return $this->api->createProduct(
            title: $command->title,
            price: $command->price,
            description: $command->description,
            category: $command->category,
            brand: $command->brand,
            stock: $command->stock,
            image: $command->image,
            specs: $command->specs,
        );
    }
}
