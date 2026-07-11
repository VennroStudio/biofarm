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

    /** @var callable(App<ContainerInterface>): void $webRoutes */
    $webRoutes = require __DIR__ . '/../config/routes/web.php';
    $webRoutes($app);

    /** @var callable(App<ContainerInterface>): void $adminRoutes */
    $adminRoutes = require __DIR__ . '/../config/routes/admin.php';
    $adminRoutes($app);

    /** @var callable(App<ContainerInterface>): void $apiRoutes */
    $apiRoutes = require __DIR__ . '/../config/routes/v1.php';
    $apiRoutes($app);

    return $app;
};
