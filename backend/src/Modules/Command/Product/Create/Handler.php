<?php

declare(strict_types=1);

namespace App\Modules\Command\Product\Create;

use App\Modules\Entity\Product\Product;
use App\Modules\Entity\Product\ProductRepository;
use DomainException;

final readonly class Handler
{
    public function __construct(
        private ProductRepository $productRepository,
    ) {}

    public function handle(Command $command): Product
    {
        $existing = $this->productRepository->findBySlug($command->slug);

        if ($existing) {
            throw new DomainException('Product with this slug already exists');
        }

        $product = Product::create(
            slug: $command->slug,
            name: $command->name,
            categoryId: $command->categoryId,
            price: $command->price,
            image: $command->image,
            weight: $command->weight,
            description: $command->description,
            shortDescription: $command->shortDescription,
            oldPrice: $command->oldPrice,
            images: $command->images,
            badge: $command->badge,
            ingredients: $command->ingredients,
            features: $command->features,
            wbLink: $command->wbLink,
            ozonLink: $command->ozonLink,
            isActive: $command->isActive,
        );

        $this->productRepository->add($product);

        return $product;
    }
}
