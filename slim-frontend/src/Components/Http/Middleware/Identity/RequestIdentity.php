<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware\Identity;

use App\Components\Exception\AuthenticationException;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;

final class RequestIdentity
{
    private const string ATTRIBUTE = 'identity';

    public static function find(ServerRequestInterface $request): ?Identity
    {
        $identity = $request->getAttribute(self::ATTRIBUTE);

        if ($identity !== null && !$identity instanceof Identity) {
            throw new LogicException('Invalid identity.');
        }

        return $identity;
    }

    public static function get(ServerRequestInterface $request): Identity
    {
        return self::find($request) ?? throw new AuthenticationException('error.unauthorized');
    }

    public static function with(ServerRequestInterface $request, Identity $identity): ServerRequestInterface
    {
        return $request->withAttribute(self::ATTRIBUTE, $identity);
    }
}
