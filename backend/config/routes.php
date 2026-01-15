<?php

declare(strict_types=1);

use App\Http\Action\V1\BlogPost\GetAllAction as BlogPostGetAllAction;
use App\Http\Action\V1\BlogPost\GetBySlugAction as BlogPostGetBySlugAction;
use App\Http\Action\V1\BlogPost\UpdateAction as BlogPostUpdateAction;
use App\Http\Action\V1\BlogPost\DeleteAction as BlogPostDeleteAction;
use App\Http\Action\V1\Category\GetAllAction as CategoryGetAllAction;
use App\Http\Action\V1\Category\CreateAction as CategoryCreateAction;
use App\Http\Action\V1\Category\UpdateAction as CategoryUpdateAction;
use App\Http\Action\V1\Category\DeleteAction as CategoryDeleteAction;
use App\Http\Action\V1\Order\CreateAction as OrderCreateAction;
use App\Http\Action\V1\Order\GetAllAction as OrderGetAllAction;
use App\Http\Action\V1\Order\GetByUserIdAction as OrderGetByUserIdAction;
use App\Http\Action\V1\Order\GetByReferrerIdAction as OrderGetByReferrerIdAction;
use App\Http\Action\V1\Order\UpdateStatusAction as OrderUpdateStatusAction;
use App\Http\Action\V1\Product\GetAllAction as ProductGetAllAction;
use App\Http\Action\V1\Product\GetBySlugAction as ProductGetBySlugAction;
use App\Http\Action\V1\Product\UpdateAction as ProductUpdateAction;
use App\Http\Action\V1\Product\DeleteAction as ProductDeleteAction;
use App\Http\Action\V1\Review\ApproveAction as ReviewApproveAction;
use App\Http\Action\V1\Review\CreateAction as ReviewCreateAction;
use App\Http\Action\V1\Review\DeleteAction as ReviewDeleteAction;
use App\Http\Action\V1\Review\GetAllAction as ReviewGetAllAction;
use App\Http\Action\V1\Review\GetByProductIdAction as ReviewGetByProductIdAction;
use App\Http\Action\V1\Review\UpdateAction as ReviewUpdateAction;
use App\Http\Action\V1\User\GetAllAction as UserGetAllAction;
use App\Http\Action\V1\User\GetCurrentAction as UserGetCurrentAction;
use App\Http\Action\V1\User\GetReferralInfoAction as UserGetReferralInfoAction;
use App\Http\Action\V1\User\LoginAction as UserLoginAction;
use App\Http\Action\V1\User\RegisterAction as UserRegisterAction;
use App\Http\Action\V1\User\UpdateProfileAction as UserUpdateProfileAction;
use App\Http\Action\V1\Withdrawal\CreateAction as WithdrawalCreateAction;
use App\Http\Action\V1\Withdrawal\GetAllAction as WithdrawalGetAllAction;
use App\Http\Action\V1\Withdrawal\GetByUserIdAction as WithdrawalGetByUserIdAction;
use App\Http\Action\V1\Withdrawal\UpdateStatusAction as WithdrawalUpdateStatusAction;
use Slim\App;

return static function (App $app, array $dependencies): void {
    $fetchers = $dependencies['fetchers'];
    $handlers = $dependencies['handlers'];
    $em = $dependencies['em'];

    // Products
    $app->get('/api/v1/products', new ProductGetAllAction($fetchers['productsGetAll']));
    $app->get('/api/v1/products/{slug}', new ProductGetBySlugAction($fetchers['productsGetBySlug']));
    $app->put('/api/v1/products/{id}', new ProductUpdateAction($handlers['productUpdate'], $em));
    $app->delete('/api/v1/products/{id}', new ProductDeleteAction($handlers['productDelete'], $em));

    // Auth/Users
    $app->get('/api/v1/users', new UserGetAllAction($fetchers['usersGetAll']));
    $app->post('/api/v1/auth/login', new UserLoginAction($fetchers['usersGetByEmail']));
    $app->post('/api/v1/auth/register', new UserRegisterAction($handlers['userCreate'], $em));
    $app->get('/api/v1/auth/me', new UserGetCurrentAction($fetchers['usersGetById']));
    $app->get('/api/v1/auth/referral-info', new UserGetReferralInfoAction($fetchers['usersGetReferralInfo']));
    $app->put('/api/v1/auth/profile', new UserUpdateProfileAction($handlers['userUpdate'], $em));

    // Orders
    $app->get('/api/v1/orders', new OrderGetAllAction($fetchers['ordersGetAll'], $dependencies['orderItemRepository']));
    $app->get('/api/v1/orders/user/{userId}', new OrderGetByUserIdAction($fetchers['ordersGetByUserId'], $dependencies['orderItemRepository']));
    $app->get('/api/v1/orders/referrer/{referrerId}', new OrderGetByReferrerIdAction($fetchers['ordersGetByReferrerId'], $dependencies['orderItemRepository']));
    $app->post('/api/v1/orders', new OrderCreateAction($handlers['orderCreate'], $handlers['orderItemCreate'], $em));
    $app->put('/api/v1/orders/{id}/status', new OrderUpdateStatusAction($handlers['orderUpdateStatus'], $em));
    $app->put('/api/v1/orders/{id}/payment-status', new \App\Http\Action\V1\Order\UpdatePaymentStatusAction($handlers['orderUpdatePaymentStatus'], $em));

    // Reviews
    $app->get('/api/v1/reviews', new ReviewGetAllAction($fetchers['reviewsGetAll']));
    $app->get('/api/v1/reviews/product', new ReviewGetByProductIdAction($fetchers['reviewsGetByProductId']));
    $app->post('/api/v1/reviews', new ReviewCreateAction($handlers['reviewCreate'], $em));
    $app->put('/api/v1/reviews/{id}', new ReviewUpdateAction($handlers['reviewUpdate'], $em));
    $app->put('/api/v1/reviews/{id}/approve', new ReviewApproveAction($handlers['reviewApprove'], $em));
    $app->delete('/api/v1/reviews/{id}', new ReviewDeleteAction($handlers['reviewDelete'], $em));

    // Blog
    $app->get('/api/v1/blog', new BlogPostGetAllAction($fetchers['blogPostsGetAll']));
    $app->get('/api/v1/blog/{slug}', new BlogPostGetBySlugAction($fetchers['blogPostsGetBySlug']));
    $app->put('/api/v1/blog/{id}', new BlogPostUpdateAction($handlers['blogPostUpdate'], $em));
    $app->delete('/api/v1/blog/{id}', new BlogPostDeleteAction($handlers['blogPostDelete'], $em));

    // Categories
    $app->get('/api/v1/categories', new CategoryGetAllAction($fetchers['categoriesGetAll']));
    $app->post('/api/v1/categories', new CategoryCreateAction($handlers['categoryCreate'], $em));
    $app->put('/api/v1/categories/{id}', new CategoryUpdateAction($handlers['categoryUpdate'], $em));
    $app->delete('/api/v1/categories/{id}', new CategoryDeleteAction($handlers['categoryDelete'], $em));

    // Withdrawals
    $app->get('/api/v1/withdrawals', new WithdrawalGetAllAction($fetchers['withdrawalsGetAll']));
    $app->get('/api/v1/withdrawals/user', new WithdrawalGetByUserIdAction($fetchers['withdrawalsGetByUserId']));
    $app->post('/api/v1/withdrawals', new WithdrawalCreateAction($handlers['withdrawalCreate'], $em));
    $app->put('/api/v1/withdrawals/{id}/status', new WithdrawalUpdateStatusAction($handlers['withdrawalUpdateStatus'], $em));
};
