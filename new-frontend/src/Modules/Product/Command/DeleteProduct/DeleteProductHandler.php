<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\DeleteProduct;

use App\Modules\Product\Api\ProductApi;
use App\Modules\Product\Api\Response\ProductDeleteResponse;

final readonly class DeleteProductHandler
{
    public function __construct(
        private ProductApi $api,
    ) {}

    public function handle(DeleteProductCommand $command): ProductDeleteResponse
    {
        return $this->api->deleteProduct($command->id);
    }
}
