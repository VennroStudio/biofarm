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
use App\Http\Action\Admin\Page\CreatePageAction;
use App\Http\Action\Admin\Page\DeletePageAction;
use App\Http\Action\Admin\Page\GetPagesAction;
use App\Http\Action\Admin\Page\GetPageTemplatesAction;
use App\Http\Action\Admin\Page\UpdatePageAction;
use App\Http\Action\Admin\ProductTaxonomy\DeleteAttributeAction;
use App\Http\Action\Admin\ProductTaxonomy\DeleteAttributeValueAction;
use App\Http\Action\Admin\ProductTaxonomy\DeleteComponentAction;
use App\Http\Action\Admin\ProductTaxonomy\DeletePurposeAction;
use App\Http\Action\Admin\ProductTaxonomy\DeleteProductGroupAction;
use App\Http\Action\Admin\ProductTaxonomy\GetAttributesAction;
use App\Http\Action\Admin\ProductTaxonomy\GetComponentsAction;
use App\Http\Action\Admin\ProductTaxonomy\GetPurposesAction;
use App\Http\Action\Admin\ProductTaxonomy\GetProductGroupsAction;
use App\Http\Action\Admin\ProductTaxonomy\SaveAttributeAction;
use App\Http\Action\Admin\ProductTaxonomy\SaveAttributeValueAction;
use App\Http\Action\Admin\ProductTaxonomy\SaveComponentAction;
use App\Http\Action\Admin\ProductTaxonomy\SavePurposeAction;
use App\Http\Action\Admin\ProductTaxonomy\SaveProductGroupAction;
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

                $group->get('/page-templates', GetPageTemplatesAction::class);
                $group->get('/pages', GetPagesAction::class);
                $group->post('/pages', CreatePageAction::class);
                $group->patch('/pages/{id}', UpdatePageAction::class);
                $group->delete('/pages/{id}', DeletePageAction::class);

                $group->get('/components', GetComponentsAction::class);
                $group->post('/components', SaveComponentAction::class);
                $group->patch('/components/{id}', SaveComponentAction::class);
                $group->delete('/components/{id}', DeleteComponentAction::class);

                $group->get('/product-purposes', GetPurposesAction::class);
                $group->post('/product-purposes', SavePurposeAction::class);
                $group->patch('/product-purposes/{id}', SavePurposeAction::class);
                $group->delete('/product-purposes/{id}', DeletePurposeAction::class);

                $group->get('/attributes', GetAttributesAction::class);
                $group->post('/attributes', SaveAttributeAction::class);
                $group->patch('/attributes/{id}', SaveAttributeAction::class);
                $group->delete('/attributes/{id}', DeleteAttributeAction::class);
                $group->post('/attributes/{attributeId}/values', SaveAttributeValueAction::class);
                $group->patch('/attribute-values/{id}', SaveAttributeValueAction::class);
                $group->delete('/attribute-values/{id}', DeleteAttributeValueAction::class);

                $group->get('/product-groups', GetProductGroupsAction::class);
                $group->post('/product-groups', SaveProductGroupAction::class);
                $group->patch('/product-groups/{id}', SaveProductGroupAction::class);
                $group->delete('/product-groups/{id}', DeleteProductGroupAction::class);

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
