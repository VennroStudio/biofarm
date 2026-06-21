<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\ProductCategory\Update;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\String\SlugGenerator;
use App\Modules\Product\Entity\ProductCategory\ProductCategoryRepository;
use App\Modules\Product\Permission\ProductPermission;
use App\Modules\Product\Service\ProductPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;

final readonly class UpdateProductCategoryHandler
{
    public function __construct(
        private ProductCategoryRepository $categoryRepository,
        private ProductPermissionService $permissionService,
        private SlugGenerator $slugGenerator,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function handle(UpdateProductCategoryCommand $command): void
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: ProductPermission::UPDATE,
        );

        $category = $this->categoryRepository->getById($command->categoryId);
        $slug = $this->slug($command->slug, $command->name);
        $existing = $this->categoryRepository->findBySlug($slug);

        if ($existing !== null && $existing->id !== $category->id) {
            throw new DomainExceptionModule(
                module: 'product',
                message: 'error.category_slug_already_exists',
                code: 2,
            );
        }

        $category->edit(
            slug: $slug,
            name: trim($command->name),
        );

        $this->cacher->delete('categories_find_all');
        $this->cacher->delete('category_by_id_' . $command->categoryId);
        $this->flusher->flush();
    }

    private function slug(?string $slug, string $name): string
    {
        $slug = $slug !== null && trim($slug) !== ''
            ? trim($slug)
            : $this->slugGenerator->generate($name);

        return $this->slugGenerator->generate($slug);
    }
}
