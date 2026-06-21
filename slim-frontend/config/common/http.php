<?php

declare(strict_types=1);

use App\Components\Http\Cookie\CookieManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

use function App\Components\env;

return [
    ResponseFactoryInterface::class => static fn (): ResponseFactoryInterface => new ResponseFactory(),

    CookieManager::class => static fn (): CookieManager => new CookieManager(
        domain: env('COOKIE_DOMAIN'),
        secure: env('COOKIE_SECURE') === 'true',
    ),
];
