<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Slim\App;

use function App\Components\env;

/** @param App<ContainerInterface> $app */
return static function (App $app): void {
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    $app->addErrorMiddleware((bool)env('APP_DEBUG', '1'), true, true);
};
