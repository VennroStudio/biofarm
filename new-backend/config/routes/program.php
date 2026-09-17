<?php

declare(strict_types=1);
use App\Components\Http\Middleware\Identity\Authenticate;
use App\Components\Http\Middleware\Identity\RequireAdmin;
use App\Http\Action\Program\ProgramAction;
use App\Http\Action\Program\TeamInvitationAction;
use Slim\App;

return static function (App $app): void {
    $app->get('/v1/team-invitations/{code}', TeamInvitationAction::class);
    foreach (['/v1/program', '/admin/api/program'] as $base) {
        $admin = str_starts_with($base, '/admin');
        $routes = [['GET', ''], ['GET', '/ledger'], ['GET', '/sales'], ['GET', '/withdrawals'], ['POST', '/withdrawals']];
        if (!$admin) {
            $routes = [...$routes, ['GET', '/team'], ['GET', '/referrals'], ['GET', '/team-invitation'], ['POST', '/join']];
        }
        if ($admin) {
            $routes = [...$routes, ['GET', '/audit'], ['POST', '/adjustments'], ['GET', '/settings'], ['PATCH', '/settings'], ['POST', '/simulate'], ['PATCH', '/withdrawals/{id}'], ['POST', '/orders/{id}/delivered']];
        }
        foreach ($routes as [$method,$suffix]) {
            $route = $app->map([$method], $base . $suffix, ProgramAction::class);
            if ($admin) {
                $route->add(RequireAdmin::class);
            }$route->add(Authenticate::class);
        }
    }
};
