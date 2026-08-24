<?php

declare(strict_types=1);

namespace App\Http\Web\Cart;

use App\Components\Setting\SiteSettings;
use App\Components\Twig\HtmlResponder;
use App\Http\View\StaticPageView;
use App\Modules\Page\Service\PageSeoProvider;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class OrderSuccessPageController implements RequestHandlerInterface
{
    public function __construct(
        private HtmlResponder $html,
        private SiteSettings $settings,
        private PageSeoProvider $seo,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->settings->bool('cart_enabled')) {
            return $this->ordersUnavailable('/order-success');
        }

        return $this->html->render('pages/cart/order-success.html.twig', [
            'page' => new StaticPageView($this->seo->systemMeta(
                'order_success',
                '/order-success',
                'Заказ оформлен — БИОФАРМ',
                'Подтверждение оформления заказа БИОФАРМ.',
                robots: 'noindex, follow',
            )),
        ]);
    }

    /**
     * @throws Exception
     */
    private function ordersUnavailable(string $path): ResponseInterface
    {
        return $this->html->render('pages/cart/unavailable.html.twig', [
            'page' => new StaticPageView($this->seo->systemMeta(
                'orders_unavailable',
                $path,
                'Заказы временно недоступны — БИОФАРМ',
                'Оформление заказов на сайте БИОФАРМ временно недоступно.',
                robots: 'noindex, follow',
            )),
        ], 404);
    }
}
