<?php

declare(strict_types=1);

use ZayMedia\Shared\Http\Middleware\HttpNotFoundRedirectExceptionHandler;

return [
    HttpNotFoundRedirectExceptionHandler::class => static function (): HttpNotFoundRedirectExceptionHandler {
        return new HttpNotFoundRedirectExceptionHandler(
            location: '/'
        );
    },
];
