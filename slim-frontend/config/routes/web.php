<?php

declare(strict_types=1);

use App\Components\Router\StaticRouteGroup as Group;
use App\Http\Web\Home\HomePageController;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/** @param App<ContainerInterface> $app */
return static function (App $app): void {
    $app->group('', new Group(static function (RouteCollectorProxy $group): void {
        $group->get('/', HomePageController::class);
    }));
};
