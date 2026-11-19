<?php

declare(strict_types=1);

namespace App\Http\Web\Cart;

use App\Components\Setting\SiteSettings;
use App\Components\Twig\HtmlResponder;
use App\Http\View\StaticPageView;
use App\Modules\Ozon\OzonPickupCheckout;
use App\Modules\Page\Service\PageSeoProvider;
use App\Modules\User\Service\Phone\PhoneAccounts;
use App\Modules\User\Service\Phone\SmsAero;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class CheckoutPageController implements RequestHandlerInterface
{
    public function __construct(
        private HtmlResponder $html,
        private SiteSettings $settings,
        private PageSeoProvider $seo,
        private OzonPickupCheckout $ozonCheckout,
        private PhoneAccounts $phones,
        private SmsAero $sms,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->settings->bool('cart_enabled')) {
            return $this->ordersUnavailable('/checkout');
        }

        $ozonMethod = $this->ozonCheckout->method();

        $testing = $this->settings->bool('testing_enabled');
        return $this->html->render('pages/cart/checkout.html.twig', [
            'ozonMethod' => $ozonMethod,
            'phoneVerificationEnabled' => !$testing && ($ozonMethod !== null || $this->phones->checkoutEnabled()),
            'smsAvailable' => !$testing && $this->sms->configured(),
            'page' => new StaticPageView($this->seo->systemMeta(
                'checkout',
                '/checkout',
                'Оформление заказа — БИОФАРМ',
                'Оформление заказа БИОФАРМ.',
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
