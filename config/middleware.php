<?php

declare(strict_types=1);

use App\Components\Http\Middleware\AccessDeniedExceptionHandler;
use App\Components\Http\Middleware\AuthenticationExceptionHandler;
use App\Components\Http\Middleware\ClearEmptyInput;
use App\Components\Http\Middleware\CorsMiddleware;
use App\Components\Http\Middleware\DenormalizationExceptionHandler;
use App\Components\Http\Middleware\DomainExceptionHandler;
use App\Components\Http\Middleware\DomainExceptionModuleHandler;
use App\Components\Http\Middleware\HttpNotFoundExceptionHandler;
use App\Components\Http\Middleware\InvalidArgumentExceptionHandler;
use App\Components\Http\Middleware\MethodNotAllowedExceptionHandler;
use App\Components\Http\Middleware\NotFoundExceptionHandler;
use App\Components\Http\Middleware\TranslatorLocale;
use App\Components\Http\Middleware\ValidationExceptionHandler;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Middleware\ErrorMiddleware;

/** @param App<ContainerInterface> $app */
return static function (App $app): void {
    $app->addBodyParsingMiddleware();
    $app->add(TranslatorLocale::class);
    $app->add(ClearEmptyInput::class);
    $app->add(DenormalizationExceptionHandler::class);
    $app->add(ValidationExceptionHandler::class);
    $app->add(AuthenticationExceptionHandler::class);
    $app->add(DomainExceptionModuleHandler::class);
    $app->add(DomainExceptionHandler::class);
    $app->add(AccessDeniedExceptionHandler::class);
    $app->add(NotFoundExceptionHandler::class);
    $app->add(HttpNotFoundExceptionHandler::class);
    $app->add(MethodNotAllowedExceptionHandler::class);
    $app->add(InvalidArgumentExceptionHandler::class);
    $app->add(ErrorMiddleware::class);
    $app->addRoutingMiddleware();
    $app->add(CorsMiddleware::class);
};
