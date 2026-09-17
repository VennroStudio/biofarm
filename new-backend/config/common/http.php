<?php

declare(strict_types=1);

use App\Components\Http\Cookie\CookieManager;
use App\Components\Http\Cookie\AdminSessionCookie;
use App\Components\Auth\JwtTokenService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

use function App\Components\env;

return [
    AdminSessionCookie::class => static fn (ContainerInterface $c): AdminSessionCookie => new AdminSessionCookie(
        secure: env('COOKIE_SECURE') === 'true',
        ttl: $c->get(JwtTokenService::class)->getRefreshTtl(),
    ),
    ResponseFactoryInterface::class => static fn (): ResponseFactoryInterface => new ResponseFactory(),

    CookieManager::class => static fn (): CookieManager => new CookieManager(
        domain: env('COOKIE_DOMAIN'),
        secure: env('COOKIE_SECURE') === 'true',
    ),
];
