<?php

declare(strict_types=1);

use App\Components\Http\Middleware\Identity\OptionalAuthenticate;
use App\Http\Action\v1\Auth\CheckoutPhoneAction;
use Slim\App;
use Slim\Psr7\Response;

return static function (App $app): void {
    foreach (['start', 'verify', 'complete'] as $action) {
        // Kept outside /v1/auth so that an existing customer's expired session can be refreshed.
        $app->post('/v1/checkout/phone/' . $action, CheckoutPhoneAction::class)->add(OptionalAuthenticate::class);
        $app->post('/v1/phone-login/' . $action, CheckoutPhoneAction::class);
        $app->post('/v1/phone-registration/' . $action, CheckoutPhoneAction::class);
    }
    // The browser polls our backend. Only a server-to-server status response can authenticate a session.
    $app->post('/webhooks/smsaero/mobile-id', fn (): Response => new Response(200));
};
