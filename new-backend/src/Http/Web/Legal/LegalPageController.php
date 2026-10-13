<?php

declare(strict_types=1);

namespace App\Http\Web\Legal;

use App\Components\Twig\HtmlResponder;
use App\Http\View\StaticPageView;
use App\Modules\Page\Service\PageSeoProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class LegalPageController implements RequestHandlerInterface
{
    public const array PAGES = [
        'privacy' => 'Политика в отношении обработки персональных данных',
        'personal-data-consent' => 'Согласие на обработку персональных данных',
        'oferta' => 'Публичная оферта интернет-магазина БИОФАРМ',
        'cookie-policy' => 'Политика использования файлов cookie',
        'user-agreement' => 'Пользовательское соглашение сайта БИОФАРМ',
        'marketing-consent' => 'Согласие на получение рекламы и рекламную обработку персональных данных',
        'vozvrat' => 'ВОЗВРАТ И ОБМЕН ТОВАРОВ',
        'bonus-rules' => 'ПРАВИЛА БОНУСНОЙ ПРОГРАММЫ BIOFARM',
    ];
    public function __construct(private HtmlResponder $html, private PageSeoProvider $seo) {}
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $slug = trim($request->getUri()->getPath(), '/');
        $title = self::PAGES[$slug];
        return $this->html->render('pages/legal/document.html.twig', [
            'documentSlug' => $slug,
            'documentTitle' => $title,
            'page' => new StaticPageView($this->seo->systemMeta($slug, '/' . $slug, $title . ' — БИОФАРМ', $title . '. Редакция от 04.10.2026.')),
        ]);
    }
}
