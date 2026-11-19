<?php

declare(strict_types=1);

use App\Components\Http\Middleware\Identity\OptionalAuthenticate;
use App\Http\Action\Ozon\OzonCallbackAction;
use App\Http\Action\Ozon\OzonPickupPointsAction;
use App\Http\Action\Ozon\OzonPickupQuoteAction;
use App\Http\Action\Ozon\OzonTrackingAction;
use App\Http\Action\Ozon\OzonWebhookAction;
use Slim\App;

return static function (App $app): void {
    $app->post('/v1/ozon-delivery/quote', OzonPickupQuoteAction::class)->add(OptionalAuthenticate::class);
    $app->get('/v1/ozon-delivery/pickup-points', OzonPickupPointsAction::class);
    $app->get('/v1/ozon-delivery/orders/{orderId}/tracking', OzonTrackingAction::class)->add(OptionalAuthenticate::class);
    $app->get('/ozon/callback', OzonCallbackAction::class);
    $app->post('/webhooks/ozon/{secret:[A-Za-z0-9_-]+}', OzonWebhookAction::class);
};
