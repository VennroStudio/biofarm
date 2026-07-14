<?php

declare(strict_types=1);

namespace App\Http\Web\Profile;

use App\Components\Twig\HtmlResponder;
use App\Http\View\StaticPageView;
use App\Modules\Page\Service\PageSeoProvider;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ProfilePageController implements RequestHandlerInterface
{
    public function __construct(
        private HtmlResponder $html,
        private PageSeoProvider $seo,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        unset($request);

        return $this->html->render('pages/profile/index.html.twig', [
            'page' => new StaticPageView($this->seo->systemMeta(
                'profile',
                '/profile',
                'Профиль — БИОФАРМ',
                'Профиль, заказы и реферальная программа БИОФАРМ.',
                robots: 'noindex, follow',
            )),
        ]);
    }
}
