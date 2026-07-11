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
use App\Http\Action\v1\Blog\CreateBlogPostAction;
use App\Http\Action\v1\Blog\DeleteBlogPostAction;
use App\Http\Action\v1\Blog\GetBlogPostByIdAction;
use App\Http\Action\v1\Blog\GetBlogPostsAction;
use App\Http\Action\v1\Blog\UpdateBlogPostAction;
use App\Http\Action\v1\OpenApiAction;
use App\Http\Action\v1\Order\CreateOrderAction;
use App\Http\Action\v1\Order\DeleteOrderAction;
use App\Http\Action\v1\Order\GetOrderByIdAction;
use App\Http\Action\v1\Order\GetOrdersAction;
use App\Http\Action\v1\Order\UpdateOrderAction;
use App\Http\Action\v1\Product\CreateProductAction;
use App\Http\Action\v1\Product\CreateProductCategoryAction;
use App\Http\Action\v1\Product\DeleteProductAction;
use App\Http\Action\v1\Product\DeleteProductCategoryAction;
use App\Http\Action\v1\Product\GetProductByIdAction;
use App\Http\Action\v1\Product\GetProductCategoriesAction;
use App\Http\Action\v1\Product\GetProductCategoryByIdAction;
use App\Http\Action\v1\Product\GetProductsAction;
use App\Http\Action\v1\Product\UpdateProductAction;
use App\Http\Action\v1\Product\UpdateProductCategoryAction;
use App\Http\Action\v1\Review\CreateReviewAction;
use App\Http\Action\v1\Review\DeleteReviewAction;
use App\Http\Action\v1\Review\GetReviewByIdAction;
use App\Http\Action\v1\Review\GetReviewsAction;
use App\Http\Action\v1\Review\UpdateReviewAction;
use App\Http\Action\v1\User\CreateUserAction;
use App\Http\Action\v1\User\DeleteAvatarAction;
use App\Http\Action\v1\User\DeleteUserAction;
use App\Http\Action\v1\User\GetMeAction;
use App\Http\Action\v1\User\GetReferralInfoAction;
use App\Http\Action\v1\User\GetReferralOrdersAction;
use App\Http\Action\v1\User\GetUserByIdAction;
use App\Http\Action\v1\User\GetUserRolesAction;
use App\Http\Action\v1\User\GetUsersAction;
use App\Http\Action\v1\User\UpdateMeAction;
use App\Http\Action\v1\User\UploadAvatarAction;
use App\Http\Action\v1\User\UserUpdateAction;
use App\Http\Action\v1\Withdrawal\CreateWithdrawalAction;
use App\Http\Action\v1\Withdrawal\GetWithdrawalsAction;
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
            $group->get('/me', GetMeAction::class)->add(Authenticate::class);
            $group->patch('/me', UpdateMeAction::class)->add(Authenticate::class);
            $group->get('/me/referral-info', GetReferralInfoAction::class)->add(Authenticate::class);
            $group->get('/me/referral-orders', GetReferralOrdersAction::class)->add(Authenticate::class);
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

        $group->group('/products', new Group(static function (RouteCollectorProxy $group): void {
            $group->get('', GetProductsAction::class);
            $group->post('/create', CreateProductAction::class)->add(Authenticate::class);
            $group->get('/{id}', GetProductByIdAction::class);
            $group->patch('/update/{id}', UpdateProductAction::class)->add(Authenticate::class);
            $group->delete('/delete/{id}', DeleteProductAction::class)->add(Authenticate::class);
        }));

        $group->group('/product-categories', new Group(static function (RouteCollectorProxy $group): void {
            $group->get('', GetProductCategoriesAction::class);
            $group->post('/create', CreateProductCategoryAction::class)->add(Authenticate::class);
            $group->get('/{id}', GetProductCategoryByIdAction::class);
            $group->patch('/update/{id}', UpdateProductCategoryAction::class)->add(Authenticate::class);
            $group->delete('/delete/{id}', DeleteProductCategoryAction::class)->add(Authenticate::class);
        }));

        $group->group('/reviews', new Group(static function (RouteCollectorProxy $group): void {
            $group->get('', GetReviewsAction::class);
            $group->post('/create', CreateReviewAction::class)->add(Authenticate::class);
            $group->get('/{id}', GetReviewByIdAction::class);
            $group->patch('/update/{id}', UpdateReviewAction::class)->add(Authenticate::class);
            $group->delete('/delete/{id}', DeleteReviewAction::class)->add(Authenticate::class);
        }));

        $group->group('/blog', new Group(static function (RouteCollectorProxy $group): void {
            $group->get('', GetBlogPostsAction::class);
            $group->post('/create', CreateBlogPostAction::class)->add(Authenticate::class);
            $group->get('/{id}', GetBlogPostByIdAction::class);
            $group->patch('/update/{id}', UpdateBlogPostAction::class)->add(Authenticate::class);
            $group->delete('/delete/{id}', DeleteBlogPostAction::class)->add(Authenticate::class);
        }));

        $group->group('/orders', new Group(static function (RouteCollectorProxy $group): void {
            $group->get('', GetOrdersAction::class)->add(Authenticate::class);
            $group->post('/create', CreateOrderAction::class)->add(Authenticate::class);
            $group->get('/{id}', GetOrderByIdAction::class)->add(Authenticate::class);
            $group->patch('/update/{id}', UpdateOrderAction::class)->add(Authenticate::class);
            $group->delete('/delete/{id}', DeleteOrderAction::class)->add(Authenticate::class);
        }));

        $group->group('/withdrawals', new Group(static function (RouteCollectorProxy $group): void {
            $group->get('', GetWithdrawalsAction::class)->add(Authenticate::class);
            $group->post('/create', CreateWithdrawalAction::class)->add(Authenticate::class);
        }));
    }));
};
