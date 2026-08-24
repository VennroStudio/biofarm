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
use Doctrine\DBAL\Connection;
use Throwable;

final readonly class DeleteProductHandler
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductPermissionService $permissionService,
        private Cacher $cacher,
        private FlusherInterface $flusher,
        private Connection $connection,
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
        $now = gmdate('Y-m-d H:i:s');

        $this->connection->beginTransaction();
        try {
            $product->markDeleted();
            $this->connection->delete('product_attribute_values', ['product_id' => $command->productId]);
            $this->connection->delete('product_group_items', ['product_id' => $command->productId]);
            $this->connection->delete('product_blog_posts', ['product_id' => $command->productId]);
            $this->connection->delete('product_images', ['product_id' => $command->productId]);
            $this->connection->update(
                'certificates',
                ['product_id' => null, 'updated_at' => $now],
                ['product_id' => $command->productId],
            );
            $this->flusher->flush();
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }

        $this->cacher->deleteTag('products');
    }
}
