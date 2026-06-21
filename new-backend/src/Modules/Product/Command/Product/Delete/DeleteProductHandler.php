<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\Product\Delete;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Modules\Product\Entity\Product\ProductRepository;
use App\Modules\Product\Permission\ProductPermission;
use App\Modules\Product\Service\ProductPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;

final readonly class DeleteProductHandler
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductPermissionService $permissionService,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function handle(DeleteProductCommand $command): void
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: ProductPermission::DELETE,
        );

        $product = $this->productRepository->getById($command->productId);
        $product->markDeleted();

        $this->cacher->deleteTag('products');
        $this->flusher->flush();
    }
}
