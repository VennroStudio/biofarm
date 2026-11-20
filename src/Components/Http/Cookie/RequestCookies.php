<?php

declare(strict_types=1);

namespace App\Components\Http\Cookie;

use App\Components\Exception\AuthenticationException;
use Psr\Http\Message\ServerRequestInterface;

final class RequestCookies
{
    private const string ATTRIBUTE = 'cookies_context';

    public static function find(ServerRequestInterface $request): ?CookieContext
    {
        /** @var CookieContext|null */
        return $request->getAttribute(self::ATTRIBUTE);
    }

    /**
     * @throws AuthenticationException
     */
    public static function get(ServerRequestInterface $request): CookieContext
    {
        return self::find($request) ?? throw new AuthenticationException('error.missing_cookie');
    }

    public static function with(ServerRequestInterface $request, CookieContext $context): ServerRequestInterface
    {
        return $request->withAttribute(self::ATTRIBUTE, $context);
    }
}
