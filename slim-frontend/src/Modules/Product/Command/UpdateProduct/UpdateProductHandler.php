<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\UpdateProduct;

use App\Modules\Product\Api\ProductApi;
use App\Modules\Product\Api\Response\ProductResponse;

final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductApi $api,
    ) {}

    public function handle(UpdateProductCommand $command): ProductResponse
    {
        return $this->api->updateProduct(
            id: $command->id,
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
