<?php

declare(strict_types=1);

namespace App\Modules\Query\Categories\GetAll;

use App\Modules\Entity\Category\CategoryRepository;

final readonly class Fetcher
{
    public function __construct(
        private CategoryRepository $repository,
    ) {}

    public function fetch(Query $query): array
    {
        if ($query->activeOnly) {
            return $this->repository->findActive();
        }
        return $this->repository->findAll();
    }
}
