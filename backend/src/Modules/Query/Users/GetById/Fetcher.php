<?php

declare(strict_types=1);

namespace App\Modules\Query\Users\GetById;

use App\Modules\Entity\User\User;
use App\Modules\Entity\User\UserRepository;

final readonly class Fetcher
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function fetch(Query $query): ?User
    {
        return $this->userRepository->findById($query->userId);
    }
}
