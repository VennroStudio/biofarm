<?php

declare(strict_types=1);

namespace App\Components\Router;

use Closure;
use Slim\Routing\RouteCollectorProxy;

final readonly class StaticRouteGroup
{
    public function __construct(
        private Closure $callable,
    ) {}

    public function __invoke(RouteCollectorProxy $group): void
    {
        ($this->callable)($group);
    }
}
