<?php

declare(strict_types=1);

namespace App\Modules\Command\Category\Update;

use App\Modules\Entity\Category\CategoryRepository;
use DomainException;

final readonly class Handler
{
    public function __construct(
        private CategoryRepository $repository,
    ) {}

    public function handle(Command $command): void
    {
        $category = $this->repository->findById($command->id);

        if (!$category) {
            throw new DomainException('Category not found');
        }

        // Проверяем уникальность slug, если он изменяется
        if ($command->slug !== null && $command->slug !== $category->getSlug()) {
            $existing = $this->repository->findBySlug($command->slug);
            if ($existing) {
                throw new DomainException('Category with this slug already exists');
            }
        }

        $category->edit(
            name: $command->name,
            slug: $command->slug,
        );
    }
}
