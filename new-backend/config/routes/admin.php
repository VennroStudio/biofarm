<?php

declare(strict_types=1);

use App\Components\Http\Middleware\Identity\Authenticate;
use App\Components\Http\Middleware\Identity\RequireAdmin;
use App\Components\Router\StaticRouteGroup as Group;
use App\Http\Action\Admin\Auth\LoginAction as AdminLoginAction;
use App\Http\Action\Admin\Auth\LogoutAction as AdminLogoutAction;
use App\Http\Action\Admin\Auth\MeAction as AdminMeAction;
use App\Http\Action\Admin\Dashboard\GetDashboardAction;
use App\Http\Action\Admin\Media\DeleteMediaAction;
use App\Http\Action\Admin\Media\UploadMediaAction;
use App\Http\Action\Admin\Order\UpdateOrderPaymentStatusAction;
use App\Http\Action\Admin\Order\UpdateOrderStatusAction;
use App\Http\Action\Admin\Review\ApproveReviewAction;
use App\Http\Action\Admin\Setting\GetSettingsAction;
use App\Http\Action\Admin\Setting\UpdateSettingsAction;
use App\Http\Action\Admin\User\GetUsersAction as AdminGetUsersAction;
use App\Http\Action\Admin\User\UpdateUserProfileAction;
use App\Http\Action\Admin\Withdrawal\CreateWithdrawalAction;
use App\Http\Action\Admin\Withdrawal\GetWithdrawalsAction;
use App\Http\Action\Admin\Withdrawal\UpdateWithdrawalStatusAction;
use App\Http\Web\Admin\AdminPageController;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/** @param App<ContainerInterface> $app */
return static function (App $app): void {
    $app->group('/admin', new Group(static function (RouteCollectorProxy $group): void {
        $group->group('/api', new Group(static function (RouteCollectorProxy $group): void {
            $group->post('/auth/login', AdminLoginAction::class);

            $protected = $group->group('', new Group(static function (RouteCollectorProxy $group): void {
                $group->get('/auth/me', AdminMeAction::class);
                $group->post('/auth/logout', AdminLogoutAction::class);
                $group->get('/dashboard', GetDashboardAction::class);

                $group->get('/settings', GetSettingsAction::class);
                $group->patch('/settings', UpdateSettingsAction::class);

                $group->post('/media', UploadMediaAction::class);
                $group->delete('/media/{id}', DeleteMediaAction::class);

                $group->get('/users', AdminGetUsersAction::class);
                $group->patch('/users/{id}', UpdateUserProfileAction::class);

                $group->get('/withdrawals', GetWithdrawalsAction::class);
                $group->post('/withdrawals', CreateWithdrawalAction::class);
                $group->patch('/withdrawals/{id}/status', UpdateWithdrawalStatusAction::class);

                $group->patch('/reviews/{id}/approve', ApproveReviewAction::class);
                $group->patch('/orders/{id}/status', UpdateOrderStatusAction::class);
                $group->patch('/orders/{id}/payment-status', UpdateOrderPaymentStatusAction::class);
            }));
            $protected->add(RequireAdmin::class);
            $protected->add(Authenticate::class);
        }));

        $group->get('', AdminPageController::class);
        $group->get('/{path:.*}', AdminPageController::class);
    }));
};
