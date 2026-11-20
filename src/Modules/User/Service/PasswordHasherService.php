<?php

declare(strict_types=1);

namespace App\Modules\User\Service;

final readonly class PasswordHasherService
{
    public function __construct(
        private int $memoryCost = PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
        private int $timeCost = PASSWORD_ARGON2_DEFAULT_TIME_COST,
        private int $threads = PASSWORD_ARGON2_DEFAULT_THREADS,
    ) {}

    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2I, [
            'memory_cost' => $this->memoryCost,
            'time_cost'   => $this->timeCost,
            'threads'     => $this->threads,
        ]);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
