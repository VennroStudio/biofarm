<?php

declare(strict_types=1);
use App\Components\Http\Middleware\Identity\Authenticate;
use App\Components\Http\Middleware\Identity\RequireAdmin;
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
        $group->post('/offers', OfferAction::class);
        $group->patch('/offers/{id}', OfferAction::class);
        $group->get('/promo-requests', OfferAction::class);
        $group->post('/promo-requests', OfferAction::class);
    }));
    $group->add(Authenticate::class);
    $admin = $app->group('/admin/api/program', new Group(static function (RouteCollectorProxy $group): void {
        $group->get('/promo-requests', OfferAction::class);
        $group->patch('/promo-requests/{id}', OfferAction::class);
    }));
    $admin->add(RequireAdmin::class)->add(Authenticate::class);
};
