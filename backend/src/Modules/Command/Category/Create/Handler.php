<?php

declare(strict_types=1);

namespace App\Modules\Command\Category\Create;

use App\Modules\Entity\Category\Category;
use App\Modules\Entity\Category\CategoryRepository;
use DomainException;

final readonly class Handler
{
    public function __construct(
        private CategoryRepository $repository,
    ) {}

    public function handle(Command $command): Category
    {
        $existing = $this->repository->findBySlug($command->slug);

        if ($existing) {
            throw new DomainException('Category with this slug already exists');
        }

        $category = Category::create(
            slug: $command->slug,
            name: $command->name,
        );

        $this->repository->add($category);

        return $category;
    }
}
