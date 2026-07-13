<?php

declare(strict_types=1);

use App\Components\Router\StaticRouteGroup as Group;
use App\Http\Web\Auth\LoginPageController;
use App\Http\Web\Blog\BlogPageController;
use App\Http\Web\Blog\BlogPostPageController;
use App\Http\Web\Cart\CartPageController;
use App\Http\Web\Cart\CheckoutPageController;
use App\Http\Web\Cart\OrderSuccessPageController;
use App\Http\Web\Catalog\CatalogPageController;
use App\Http\Web\Feedback\FeedbackController;
use App\Http\Web\Home\HomePageController;
use App\Http\Web\Legal\PrivacyPolicyPageController;
use App\Http\Web\Legal\PublicOfferPageController;
use App\Http\Web\Profile\ProfilePageController;
use App\Http\Web\Product\CreateProductController;
use App\Http\Web\Product\DeleteProductController;
use App\Http\Web\Product\ProductPageController;
use App\Http\Web\Product\UpdateProductController;
use App\Http\Web\Seo\RobotsController;
use App\Http\Web\Seo\SitemapController;
use App\Http\Web\System\HealthController;
use App\Http\Web\System\ReadinessController;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/** @param App<ContainerInterface> $app */
return static function (App $app): void {
    $app->group('', new Group(static function (RouteCollectorProxy $group): void {
        $group->get('/', HomePageController::class);
        $group->get('/robots.txt', RobotsController::class);
        $group->get('/sitemap.xml', SitemapController::class);
        $group->get('/catalog', CatalogPageController::class);
        $group->get('/catalog/{categorySlug}/{subcategorySlug}/sostav/{componentSlug}', CatalogPageController::class);
        $group->get('/catalog/{categorySlug}/{subcategorySlug}/dlya/{purposeSlug}', CatalogPageController::class);
        $group->get('/catalog/{categorySlug}/sostav/{componentSlug}', CatalogPageController::class);
        $group->get('/catalog/{categorySlug}/dlya/{purposeSlug}', CatalogPageController::class);
        $group->get('/catalog/{categorySlug}/{subcategorySlug}', CatalogPageController::class);
        $group->get('/catalog/{categorySlug}', CatalogPageController::class);
        $group->get('/product/{slug}', ProductPageController::class);
        $group->get('/blog', BlogPageController::class);
        $group->get('/blog/{slug}', BlogPostPageController::class);
        $group->get('/cart', CartPageController::class);
        $group->get('/checkout', CheckoutPageController::class);
        $group->get('/order-success', OrderSuccessPageController::class);
        $group->get('/login', LoginPageController::class);
        $group->get('/profile', ProfilePageController::class);
        $group->get('/privacy', PrivacyPolicyPageController::class);
        $group->get('/oferta', PublicOfferPageController::class);

        $group->post('/feedback', FeedbackController::class);
        $group->post('/products/create', CreateProductController::class);
        $group->post('/products/update', UpdateProductController::class);
        $group->post('/products/delete', DeleteProductController::class);

        $group->get('/healthz', HealthController::class);
        $group->get('/readyz', ReadinessController::class);
    }));
};
