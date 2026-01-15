<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

return static function (ContainerInterface $container): App {
    $app = AppFactory::createFromContainer($container);

    /** @psalm-suppress InvalidArgument */
    (require __DIR__ . '/../config/middleware.php')($app);
    /** @psalm-suppress InvalidArgument */
    (require __DIR__ . '/../config/routes/v1.php')($app);

    return $app;
};
