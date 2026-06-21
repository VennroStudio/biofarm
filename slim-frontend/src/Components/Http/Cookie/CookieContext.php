<?php

declare(strict_types=1);

namespace App\Components\Http\Cookie;

final readonly class CookieContext
{
    public function __construct(
        public string $refreshToken = '',
        public string $loggedIn = '',
    ) {}

    /**
     * @return array<string, array{name: string, ttl: int, httpOnly: bool}>
     */
    public function getDefinition(): array
    {
        return [
            'refreshToken' => [
                'name'     => 'refresh_token',
                'ttl'      => 2592000,
                'httpOnly' => true,
            ],
            'loggedIn'     => [
                'name'     => 'logged_in',
                'ttl'      => 2592000,
                'httpOnly' => false,
            ],
        ];
    }
}
