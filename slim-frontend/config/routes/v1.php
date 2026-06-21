<?php

declare(strict_types=1);

use App\Components\Http\Middleware\Cookie\ExtractCookies;
use App\Components\Http\Middleware\Identity\Authenticate;
use App\Components\Router\StaticRouteGroup as Group;
use App\Http\Action\v1\Auth\ConfirmEmailAction;
use App\Http\Action\v1\Auth\LoginAction;
use App\Http\Action\v1\Auth\LogoutAction;
use App\Http\Action\v1\Auth\PasswordResetAction;
use App\Http\Action\v1\Auth\PasswordResetConfirmAction;
use App\Http\Action\v1\Auth\RefreshTokenAction;
use App\Http\Action\v1\OpenApiAction;
use App\Http\Action\v1\User\CreateUserAction;
use App\Http\Action\v1\User\DeleteAvatarAction;
use App\Http\Action\v1\User\DeleteUserAction;
use App\Http\Action\v1\User\GetUserByIdAction;
use App\Http\Action\v1\User\GetUserRolesAction;
use App\Http\Action\v1\User\GetUsersAction;
use App\Http\Action\v1\User\UploadAvatarAction;
use App\Http\Action\v1\User\UserUpdateAction;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/** @param App<ContainerInterface> $app */
return static function (App $app): void {
    $app->group('/v1', new Group(static function (RouteCollectorProxy $group): void {
        $group->get('', OpenApiAction::class);

        $group->group('/users', new Group(static function (RouteCollectorProxy $group): void {
            $group->get('', GetUsersAction::class)->add(Authenticate::class);
            $group->post('/create', CreateUserAction::class);
            $group->get('/roles', GetUserRolesAction::class)->add(Authenticate::class);
            $group->get('/{id}', GetUserByIdAction::class)->add(Authenticate::class);
            $group->patch('/update/{id}', UserUpdateAction::class)->add(Authenticate::class);
            $group->delete('/delete/{id}', DeleteUserAction::class)->add(Authenticate::class);
            $group->post('/{id}/avatar', UploadAvatarAction::class)->add(Authenticate::class);
            $group->delete('/{id}/avatar', DeleteAvatarAction::class)->add(Authenticate::class);
        }));

        $group->group('/auth', new Group(static function (RouteCollectorProxy $group): void {
            $group->post('/login', LoginAction::class);
            $group->post('/refresh', RefreshTokenAction::class)->add(ExtractCookies::class);
            $group->post('/logout', LogoutAction::class)->add(ExtractCookies::class);
            $group->post('/confirm-email', ConfirmEmailAction::class);
            $group->post('/password-reset', PasswordResetAction::class);
            $group->post('/password-reset/confirm', PasswordResetConfirmAction::class);
        }));
    }));
};
