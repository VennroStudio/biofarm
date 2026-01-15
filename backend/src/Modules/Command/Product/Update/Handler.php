<?php

declare(strict_types=1);

namespace App\Modules\Command\Product\Update;

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
        $product = $this->productRepository->getById($command->id);

        // Generate slug from name if not provided
        $slug = $command->slug ?? strtolower(trim(preg_replace('/[^\w\s-]/', '', $command->name)));
        $slug = preg_replace('/[-\s]+/', '-', $slug);
        $slug = trim($slug, '-');

        $product->edit(
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
            slug: $slug,
        );

        return $product;
    }
}
