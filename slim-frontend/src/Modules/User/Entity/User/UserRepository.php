<?php

declare(strict_types=1);

namespace App\Modules\User\Entity\User;

interface UserRepository
{
    public function add(User $user): void;

    public function remove(User $user): void;

    public function getById(int $id): User;

    public function findById(int $id): ?User;
}
