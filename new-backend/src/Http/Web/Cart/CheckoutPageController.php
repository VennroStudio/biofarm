<?php

declare(strict_types=1);

namespace App\Http\Web\Cart;

use App\Components\Setting\SiteSettings;
use App\Components\Twig\HtmlResponder;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class CheckoutPageController implements RequestHandlerInterface
{
    public function __construct(
        private HtmlResponder $html,
        private SiteSettings $settings,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->settings->bool('cart_enabled')) {
            return $this->responseFactory->createResponse(303)->withHeader('Location', '/catalog');
        }

        return $this->html->render('pages/cart/checkout.html.twig');
    }
}
