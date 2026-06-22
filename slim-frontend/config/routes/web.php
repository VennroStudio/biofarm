<?php

declare(strict_types=1);

use App\Components\Router\StaticRouteGroup as Group;
use App\Http\Web\Home\HomePageController;
use App\Http\Web\Product\CreateProductController;
use App\Http\Web\Product\DeleteProductController;
use App\Http\Web\Product\UpdateProductController;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/** @param App<ContainerInterface> $app */
return static function (App $app): void {
    $app->group('', new Group(static function (RouteCollectorProxy $group): void {
        $group->get('/', HomePageController::class);
        $group->post('/products/create', CreateProductController::class);
        $group->post('/products/update', UpdateProductController::class);
        $group->post('/products/delete', DeleteProductController::class);
    }));
};
