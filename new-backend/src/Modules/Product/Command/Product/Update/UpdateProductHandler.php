<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\Product\Update;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\String\SlugGenerator;
use App\Modules\Product\Entity\Product\ProductRepository;
use App\Modules\Product\Entity\ProductCategory\ProductCategoryRepository;
use App\Modules\Product\Permission\ProductPermission;
use App\Modules\Product\Service\ProductFacetSyncer;
use App\Modules\Product\Service\ProductImageSyncer;
use App\Modules\Product\Service\ProductPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;

final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductCategoryRepository $categoryRepository,
        private ProductPermissionService $permissionService,
        private ProductImageSyncer $productImageSyncer,
        private ProductFacetSyncer $productFacetSyncer,
        private SlugGenerator $slugGenerator,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function handle(UpdateProductCommand $command): int
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: ProductPermission::UPDATE,
        );

        $product = $this->productRepository->getById($command->productId);
        $this->assertCategoryExists($command->categoryId);

        $slug = $this->slug($command->slug, $command->name);
        $existing = $this->productRepository->findBySlug($slug);

        if ($existing !== null && $existing->id !== $product->id) {
            throw new DomainExceptionModule(
                module: 'product',
                message: 'error.product_slug_already_exists',
                code: 12,
            );
        }

        $product->edit(
            slug: $slug,
            name: trim($command->name),
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
            h1: $command->h1,
            seoTitle: $command->seoTitle,
            seoDescription: $command->seoDescription,
            imageAlt: $command->imageAlt,
            sku: $command->sku,
            gtin: $command->gtin,
            availability: $command->availability,
        );

        $this->productImageSyncer->sync(
            productId: $command->productId,
            mainImage: $product->image,
            alt: $product->imageAlt,
            title: $product->name,
            images: $product->images,
            productImages: $command->productImages,
        );
        $this->productFacetSyncer->sync(
            productId: $command->productId,
            attributeValueIds: $command->attributeValueIds,
            componentIds: $command->componentIds,
            purposeIds: $command->purposeIds,
            productGroupId: $command->productGroupId,
        );
        $this->deleteCache();
        $this->flusher->flush();

        return $command->productId;
    }

    private function slug(?string $slug, string $name): string
    {
        $slug = $slug !== null && trim($slug) !== ''
            ? trim($slug)
            : $this->slugGenerator->generate($name);

        return $this->slugGenerator->generate($slug);
    }

    private function assertCategoryExists(string $categoryId): void
    {
        if (ctype_digit($categoryId)) {
            $this->categoryRepository->getById((int)$categoryId);
            return;
        }

        if ($this->categoryRepository->findBySlug($categoryId) === null) {
            throw new DomainExceptionModule(
                module: 'product',
                message: 'error.category_not_found',
                code: 1,
            );
        }
    }

    private function deleteCache(): void
    {
        $this->cacher->deleteTag('products');
    }
}
