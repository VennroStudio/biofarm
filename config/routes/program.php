<?php

declare(strict_types=1);
use App\Components\Http\Middleware\Identity\Authenticate;
use App\Components\Http\Middleware\Identity\RequireAdmin;
use App\Http\Action\Program\ProgramAction;
use Slim\App;

return static function (App $app): void {
    foreach (['/v1/program', '/admin/api/program'] as $base) {
        $admin = str_starts_with($base, '/admin');
        $routes = [['GET', ''], ['GET', '/ledger']];
        if (!$admin) {
            $routes = [...$routes, ['GET', '/referrals'], ['GET', '/sales']];
        }
        if ($admin) {
            $routes = [...$routes, ['GET', '/audit'], ['POST', '/adjustments'], ['GET', '/settings'], ['PATCH', '/settings'], ['POST', '/simulate'], ['POST', '/orders/{id}/delivered']];
        }
        foreach ($routes as [$method,$suffix]) {
            $route = $app->map([$method], $base . $suffix, ProgramAction::class);
            if ($admin) {
                $route->add(RequireAdmin::class);
            }$route->add(Authenticate::class);
        }
    }
};
