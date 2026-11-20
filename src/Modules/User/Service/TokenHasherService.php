<?php

declare(strict_types=1);

namespace App\Modules\User\Service;

use Random\RandomException;

final readonly class TokenHasherService
{
    /**
     * @throws RandomException
     */
    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function verify(string $token, string $hash): bool
    {
        return hash_equals($this->hash($token), $hash);
    }
}
