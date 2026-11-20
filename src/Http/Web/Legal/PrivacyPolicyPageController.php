<?php

declare(strict_types=1);

namespace App\Http\Web\Legal;

use App\Components\Twig\HtmlResponder;
use App\Http\View\StaticPageView;
use App\Modules\Page\Service\PageSeoProvider;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class PrivacyPolicyPageController implements RequestHandlerInterface
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

        return $this->html->render('pages/legal/privacy.html.twig', [
            'page' => new StaticPageView($this->seo->systemMeta(
                'privacy',
                '/privacy',
                'Политика конфиденциальности — БИОФАРМ',
                'Политика конфиденциальности БИОФАРМ.',
            )),
        ]);
    }
}
