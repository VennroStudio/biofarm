<?php

declare(strict_types=1);

namespace App\Modules\Product\Command\ProductCategory\Create;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\String\SlugGenerator;
use App\Modules\Product\Entity\ProductCategory\ProductCategory;
use App\Modules\Product\Entity\ProductCategory\ProductCategoryRepository;
use App\Modules\Product\Permission\ProductPermission;
use App\Modules\Product\Service\ProductPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;

final readonly class CreateProductCategoryHandler
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
    public function handle(CreateProductCategoryCommand $command): void
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: ProductPermission::CREATE,
        );

        $slug = $this->slug($command->slug, $command->name);
        $this->assertSlugFree($slug);
        $this->assertParentExists($command->parentId);

        $category = ProductCategory::create(
            slug: $slug,
            name: trim($command->name),
            parentId: $command->parentId,
            h1: $command->h1,
            seoTitle: $command->seoTitle,
            seoDescription: $command->seoDescription,
            introText: $command->introText,
            bottomText: $command->bottomText,
            image: $command->image,
            isIndexable: $command->isIndexable,
            sortOrder: $command->sortOrder,
        );

        $this->categoryRepository->add($category);
        $this->cacher->delete('categories_find_all');
        $this->flusher->flush();
    }

    private function slug(?string $slug, string $name): string
    {
        $slug = $slug !== null && trim($slug) !== ''
            ? trim($slug)
            : $this->slugGenerator->generate($name);

        return $this->slugGenerator->generate($slug);
    }

    private function assertSlugFree(string $slug): void
    {
        if ($this->categoryRepository->findBySlug($slug) !== null) {
            throw new DomainExceptionModule(
                module: 'product',
                message: 'error.category_slug_already_exists',
                code: 2,
            );
        }
    }

    private function assertParentExists(?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($this->categoryRepository->findById($parentId) === null) {
            throw new DomainExceptionModule(
                module: 'product',
                message: 'error.category_parent_not_found',
                code: 3,
            );
        }
    }
}
