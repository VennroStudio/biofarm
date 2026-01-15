<?php

declare(strict_types=1);

namespace App\Modules\Query\Users\GetAll;

use App\Modules\Entity\User\UserRepository;

final readonly class Fetcher
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    /** @return \App\Modules\Entity\User\User[] */
    public function fetch(Query $query): array
    {
        return $this->repository->findAllActive();
    }
}
