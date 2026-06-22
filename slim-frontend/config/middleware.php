<?php

declare(strict_types=1);

use App\Components\Http\Middleware\RequestIdMiddleware;
use App\Components\Http\Middleware\RequestLoggingMiddleware;
use App\Components\Http\Middleware\SecurityHeadersMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\App;

use function App\Components\env_bool;

/** @param App<ContainerInterface> $app */
return static function (App $app): void {
    $container = $app->getContainer();
    if ($container === null) {
        throw new RuntimeException('Slim container is required to register middleware.');
    }

    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    /** @var RequestLoggingMiddleware $requestLogging */
    $requestLogging = $container->get(RequestLoggingMiddleware::class);
    $app->add($requestLogging);
    /** @var SecurityHeadersMiddleware $securityHeaders */
    $securityHeaders = $container->get(SecurityHeadersMiddleware::class);
    $app->add($securityHeaders);
    /** @var RequestIdMiddleware $requestId */
    $requestId = $container->get(RequestIdMiddleware::class);
    $app->add($requestId);
    /** @var LoggerInterface $logger */
    $logger = $container->get(LoggerInterface::class);
    $app->addErrorMiddleware(env_bool('APP_DEBUG', true), true, true, $logger);
};
