<?php

declare(strict_types=1);

use App\Components\Http\Middleware\Identity\Authenticate;
use App\Components\Http\Middleware\Identity\RequireAdmin;
use App\Components\Router\StaticRouteGroup as Group;
use App\Http\Action\Admin\Auth\LoginAction as AdminLoginAction;
use App\Http\Action\Admin\Auth\LogoutAction as AdminLogoutAction;
use App\Http\Action\Admin\Auth\MeAction as AdminMeAction;
use App\Http\Action\Admin\Auth\RefreshAction as AdminRefreshAction;
use App\Http\Action\Admin\BlogCategory\DeleteBlogCategoryAction;
use App\Http\Action\Admin\BlogCategory\GetBlogCategoriesAction;
use App\Http\Action\Admin\BlogCategory\SaveBlogCategoryAction;
use App\Http\Action\Admin\Certificate\GetCertificatesAction;
use App\Http\Action\Admin\Certificate\UploadCertificateFileAction;
use App\Http\Action\Admin\Dashboard\GetDashboardAction;
use App\Http\Action\Admin\Faq\GetFaqItemsAction;
use App\Http\Action\Admin\Integration\GetBitrix24SettingsAction;
use App\Http\Action\Admin\Integration\GetIntegrationErrorsAction;
use App\Http\Action\Admin\Integration\MarkAllIntegrationErrorsReadAction;
use App\Http\Action\Admin\Integration\MarkIntegrationErrorReadAction;
use App\Http\Action\Admin\Integration\TestBitrix24ConnectionAction;
use App\Http\Action\Admin\Integration\UpdateBitrix24SettingsAction;
use App\Http\Action\Admin\Material\MaterialAction;
use App\Http\Action\Admin\Media\DeleteMediaAction;
use App\Http\Action\Admin\Media\UploadMediaAction;
use App\Http\Action\Admin\Order\UpdateOrderDetailsAction;
use App\Http\Action\Admin\Order\UpdateOrderPaymentStatusAction;
use App\Http\Action\Admin\Order\UpdateOrderStatusAction;
use App\Http\Action\Admin\Page\CreatePageAction;
use App\Http\Action\Admin\Page\DeletePageAction;
use App\Http\Action\Admin\Page\GetPagesAction;
use App\Http\Action\Admin\Page\GetPageTemplatesAction;
use App\Http\Action\Admin\Page\UpdatePageAction;
use App\Http\Action\Admin\ProductTaxonomy\DeleteAttributeAction;
use App\Http\Action\Admin\ProductTaxonomy\DeleteAttributeValueAction;
use App\Http\Action\Admin\ProductTaxonomy\DeleteProductGroupAction;
use App\Http\Action\Admin\ProductTaxonomy\GetAttributesAction;
use App\Http\Action\Admin\ProductTaxonomy\GetProductGroupsAction;
use App\Http\Action\Admin\ProductTaxonomy\SaveAttributeAction;
use App\Http\Action\Admin\ProductTaxonomy\SaveAttributeValueAction;
use App\Http\Action\Admin\ProductTaxonomy\SaveProductGroupAction;
use App\Http\Action\Admin\PromoCode\DeletePromoCodeAction;
use App\Http\Action\Admin\PromoCode\GetPromoCodesAction;
use App\Http\Action\Admin\PromoCode\SavePromoCodeAction;
use App\Http\Action\Admin\Review\ApproveReviewAction;
use App\Http\Action\Admin\Setting\ChangePasswordAction;
use App\Http\Action\Admin\Setting\GetSettingsAction;
use App\Http\Action\Admin\Setting\UpdateSettingsAction;
use App\Http\Action\Admin\User\GetUserDetailsAction;
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
            $group->post('/auth/refresh', AdminRefreshAction::class);
            $group->post('/auth/logout', AdminLogoutAction::class);

            $protected = $group->group('', new Group(static function (RouteCollectorProxy $group): void {
                $group->get('/auth/me', AdminMeAction::class);
                $group->get('/dashboard', GetDashboardAction::class);

                $group->get('/settings', GetSettingsAction::class);
                $group->patch('/settings', UpdateSettingsAction::class);
                $group->patch('/settings/password', ChangePasswordAction::class);
                $group->get('/integrations/bitrix24', GetBitrix24SettingsAction::class);
                $group->patch('/integrations/bitrix24', UpdateBitrix24SettingsAction::class);
                $group->post('/integrations/bitrix24/test', TestBitrix24ConnectionAction::class);
                $group->get('/integration-errors', GetIntegrationErrorsAction::class);
                $group->patch('/integration-errors/read-all', MarkAllIntegrationErrorsReadAction::class);
                $group->patch('/integration-errors/{id}/read', MarkIntegrationErrorReadAction::class);

                $group->get('/promo-codes', GetPromoCodesAction::class);
                $group->post('/promo-codes', SavePromoCodeAction::class);
                $group->patch('/promo-codes/{id}', SavePromoCodeAction::class);
                $group->delete('/promo-codes/{id}', DeletePromoCodeAction::class);

                $materialAction = MaterialAction::class;
                $group->get('/material-targets', $materialAction)->setArgument('resource', 'targets');
                $group->get('/material-targets/{type}/{id}/selections', $materialAction)->setArgument('resource', 'selections');
                $group->get('/material-categories/{kind:certificate}', $materialAction)->setArgument('resource', 'categories');
                $group->post('/material-categories/{kind:certificate}', $materialAction)->setArgument('resource', 'categories');
                $group->map(['PATCH', 'DELETE'], '/material-categories/{kind:certificate}/{id:[0-9]+}', $materialAction)->setArgument('resource', 'categories');
                $group->post('/materials/{kind}/bulk-attach', $materialAction)->setArgument('resource', 'bulk');
                $group->map(['GET', 'POST'], '/materials/{kind}', $materialAction);
                $group->map(['GET', 'PATCH', 'DELETE'], '/materials/{kind}/{id:[0-9]+}', $materialAction);

                $group->get('/certificates', GetCertificatesAction::class);
                $group->post('/certificates', $materialAction)->setArgument('kind', 'certificate');
                $group->patch('/certificates/{id}', $materialAction)->setArgument('kind', 'certificate');
                $group->delete('/certificates/{id}', $materialAction)->setArgument('kind', 'certificate');
                $group->post('/certificates/upload', UploadCertificateFileAction::class);

                $group->get('/faq-items', GetFaqItemsAction::class);
                $group->post('/faq-items', $materialAction)->setArgument('kind', 'faq');
                $group->patch('/faq-items/{id}', $materialAction)->setArgument('kind', 'faq');
                $group->delete('/faq-items/{id}', $materialAction)->setArgument('kind', 'faq');

                $group->post('/media', UploadMediaAction::class);
                $group->delete('/media/{id}', DeleteMediaAction::class);

                $group->get('/page-templates', GetPageTemplatesAction::class);
                $group->get('/pages', GetPagesAction::class);
                $group->post('/pages', CreatePageAction::class);
                $group->patch('/pages/{id}', UpdatePageAction::class);
                $group->delete('/pages/{id}', DeletePageAction::class);

                $group->get('/attributes', GetAttributesAction::class);
                $group->post('/attributes', SaveAttributeAction::class);
                $group->patch('/attributes/{id}', SaveAttributeAction::class);
                $group->delete('/attributes/{id}', DeleteAttributeAction::class);
                $group->post('/attributes/{attributeId}/values', SaveAttributeValueAction::class);
                $group->patch('/attribute-values/{id}', SaveAttributeValueAction::class);
                $group->delete('/attribute-values/{id}', DeleteAttributeValueAction::class);

                $group->get('/blog-categories', GetBlogCategoriesAction::class);
                $group->post('/blog-categories', SaveBlogCategoryAction::class);
                $group->patch('/blog-categories/{id:[0-9]+}', SaveBlogCategoryAction::class);
                $group->delete('/blog-categories/{id:[0-9]+}', DeleteBlogCategoryAction::class);

                $group->get('/product-groups', GetProductGroupsAction::class);
                $group->post('/product-groups', SaveProductGroupAction::class);
                $group->patch('/product-groups/{id}', SaveProductGroupAction::class);
                $group->delete('/product-groups/{id}', DeleteProductGroupAction::class);

                $group->get('/users', AdminGetUsersAction::class);
                $group->get('/users/{id:[0-9]+}/details', GetUserDetailsAction::class);
                $group->patch('/users/{id}', UpdateUserProfileAction::class);

                $group->get('/withdrawals', GetWithdrawalsAction::class);
                $group->post('/withdrawals', CreateWithdrawalAction::class);
                $group->patch('/withdrawals/{id}/status', UpdateWithdrawalStatusAction::class);

                $group->patch('/reviews/{id}/approve', ApproveReviewAction::class);
                $group->patch('/orders/{id}', UpdateOrderDetailsAction::class);
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
