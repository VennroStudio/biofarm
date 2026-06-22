<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

return static function (ContainerInterface $container): App {
    $app = AppFactory::createFromContainer($container);

    /** @var callable(App<ContainerInterface>): void $middleware */
    $middleware = require __DIR__ . '/../config/middleware.php';
    $middleware($app);

    /** @var callable(App<ContainerInterface>): void $routes */
    $routes = require __DIR__ . '/../config/routes/web.php';
    $routes($app);

    return $app;
};
