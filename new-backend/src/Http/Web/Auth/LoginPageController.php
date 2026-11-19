<?php

declare(strict_types=1);

namespace App\Http\Web\Auth;

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

final readonly class LoginPageController implements RequestHandlerInterface
{
    public function __construct(
        private HtmlResponder $html,
        private PageSeoProvider $seo,
        private PhoneAccounts $phones,
        private OzonPickupCheckout $ozon,
        private SmsAero $sms,
        private SiteSettings $settings,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $passwordSetup = $request->getUri()->getPath() === '/password-reset-confirm';
        $testing = $this->settings->bool('testing_enabled');

        $response = $this->html->render('pages/auth/login.html.twig', [
            'passwordSetup' => $passwordSetup,
            'phoneLoginEnabled' => $testing || $this->phones->checkoutEnabled() || $this->ozon->method() !== null,
            'smsAvailable' => !$testing && $this->sms->configured(),
            'page' => new StaticPageView($this->seo->systemMeta(
                'login',
                '/login',
                'Личный кабинет — БИОФАРМ',
                'Вход в личный кабинет БИОФАРМ.',
                robots: 'noindex, follow',
            )),
        ]);

        return $passwordSetup ? $response->withHeader('Referrer-Policy', 'no-referrer')->withHeader('Cache-Control', 'no-store') : $response;
    }
}
