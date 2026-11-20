<?php

declare(strict_types=1);
use App\Components\Http\Middleware\Identity\Authenticate;
use App\Components\Http\Middleware\Identity\RequireAdmin;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\StaticRouteGroup as Group;
use App\Http\Action\v1\PartnerOffer\OfferAction;
use App\Http\Action\v1\PartnerOffer\ValidateReferralAction;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    $app->get('/v1/referrals/{code}', ValidateReferralAction::class);
    $app->get('/v1/offers/{id}', OfferAction::class);
    $group = $app->group('/v1/program', new Group(static function (RouteCollectorProxy $group): void {
        $group->get('/offers', OfferAction::class);
        $group->get('/offers/{id}', OfferAction::class);
        $group->post('/offers', OfferAction::class);
        $group->patch('/offers/{id}', OfferAction::class);
    }));
    $group->add(Authenticate::class);
    // Explicit tombstones also prevent the admin SPA catch-all from returning HTML.
    foreach (['/v1/program', '/admin/api/program'] as $base) {
        $route = $app->map(['GET', 'POST', 'PATCH'], $base . '/promo-requests[/{id}]', fn () => new JsonDataResponse(['message' => 'Промокоды партнёрской программы удалены.'], 410));
        if (str_starts_with($base, '/admin')) {
            $route->add(RequireAdmin::class);
        }
        $route->add(Authenticate::class);
    }
};
