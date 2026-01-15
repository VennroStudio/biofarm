<?php

declare(strict_types=1);

namespace App\Components\OAuth\Generator;

use App\Components\OAuth\Entity\Client;
use App\Components\OAuth\Entity\Scope;
use DateTimeImmutable;
use Exception;

use function App\Components\env;

final class AccessToken
{
    /** @throws Exception */
    public static function for(string $userId, string $role = '', ?DateTimeImmutable $expires = null): string
    {
        $generator = new AccessTokenGenerator(env('JWT_PRIVATE_KEY_PATH'));

        $token = $generator->generate(
            new Client(
                identifier: '5',
                name: 'iOS',
                redirectUri: 'default'
            ),
            [new Scope('common')],
            new AccessTokenParams(
                userId: $userId,
                role: $role,
                expires: $expires ?? new DateTimeImmutable('+20 minute'),
            )
        );

        return (string)$token;
    }
}
