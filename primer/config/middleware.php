<?php

declare(strict_types=1);

use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use ZayMedia\Shared\Http\Middleware\AccessDeniedExceptionHandler;
use ZayMedia\Shared\Http\Middleware\AuthenticateByKey;
use ZayMedia\Shared\Http\Middleware\ClearEmptyInput;
use ZayMedia\Shared\Http\Middleware\DenormalizationExceptionHandler;
use ZayMedia\Shared\Http\Middleware\DomainExceptionHandler;
use ZayMedia\Shared\Http\Middleware\HttpNotFoundRedirectExceptionHandler;
use ZayMedia\Shared\Http\Middleware\InvalidArgumentExceptionHandler;
use ZayMedia\Shared\Http\Middleware\MethodNotAllowedExceptionHandler;
use ZayMedia\Shared\Http\Middleware\NotFoundExceptionModuleHandler;
use ZayMedia\Shared\Http\Middleware\ThrowableHandler;
use ZayMedia\Shared\Http\Middleware\UnauthorizedHttpExceptionHandler;
use ZayMedia\Shared\Http\Middleware\ValidationExceptionHandler;

return static function (App $app): void {
    $app->add(AuthenticateByKey::class);
    $app->add(AccessDeniedExceptionHandler::class);
    $app->add(MethodNotAllowedExceptionHandler::class);
    $app->add(HttpNotFoundRedirectExceptionHandler::class);
    $app->add(DomainExceptionHandler::class);
    $app->add(NotFoundExceptionModuleHandler::class);
    $app->add(DenormalizationExceptionHandler::class);
    $app->add(ValidationExceptionHandler::class);
    $app->add(InvalidArgumentExceptionHandler::class);
    $app->add(UnauthorizedHttpExceptionHandler::class);
    $app->add(ClearEmptyInput::class);
    $app->add(ThrowableHandler::class);
    $app->addBodyParsingMiddleware();
    $app->add(ErrorMiddleware::class);
};
