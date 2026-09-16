<?php

declare(strict_types=1);
use App\Components\Http\Middleware\Identity\Authenticate;
use App\Components\Http\Middleware\Identity\OptionalAuthenticate;
use App\Components\Http\Middleware\Identity\RequireAdmin;
use App\Components\Router\StaticRouteGroup as Group;
use App\Http\Action\Admin\Payment\PaymentAdminAction;
use App\Http\Action\v1\Payment\PaymentAction;
use App\Http\Action\v1\Payment\WebhookAction;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    $app->post('/v1/payments/{orderId}', PaymentAction::class)->add(OptionalAuthenticate::class);
    $app->get('/v1/payments/{orderId}', PaymentAction::class)->add(OptionalAuthenticate::class);
    $app->post('/webhooks/yookassa', WebhookAction::class);
    $group = $app->group('/admin/api/payments', new Group(static function (RouteCollectorProxy $group): void {
        $group->get('/config', PaymentAdminAction::class);
        $group->post('/reconcile', PaymentAdminAction::class);
        $group->get('/orders/{orderId}', PaymentAdminAction::class);
        $group->post('/orders/{orderId}/receipt', PaymentAdminAction::class);
        $group->post('/orders/{orderId}/refund', PaymentAdminAction::class);
    }));
    $group->add(RequireAdmin::class)->add(Authenticate::class);
};
