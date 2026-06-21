<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\ProductCategory\Delete;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Modules\Product\Entity\ProductCategory\ProductCategoryRepository;
use App\Modules\Product\Entity\Product\ProductRepository;
use App\Modules\Product\Permission\ProductPermission;
use App\Modules\Product\Service\ProductPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;

final readonly class DeleteProductCategoryHandler
{
    public function __construct(
        private ProductCategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
        private ProductPermissionService $permissionService,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function handle(DeleteProductCategoryCommand $command): void
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: ProductPermission::DELETE,
        );

        $category = $this->categoryRepository->getById($command->categoryId);

        if (
            $this->productRepository->countByCategoryId((string)$category->id) > 0
            || $this->productRepository->countByCategoryId($category->slug) > 0
        ) {
            throw new DomainExceptionModule(
                module: 'product',
                message: 'error.category_has_products',
                code: 4,
            );
        }

        $category->markDeleted();

        $this->cacher->delete('categories_find_all');
        $this->cacher->delete('category_by_id_' . $command->categoryId);
        $this->flusher->flush();
    }
}
