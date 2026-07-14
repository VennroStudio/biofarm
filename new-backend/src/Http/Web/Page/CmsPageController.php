<?php

declare(strict_types=1);

namespace App\Http\Web\Page;

use App\Components\Router\Route;
use App\Components\Twig\HtmlResponder;
use App\Http\Unifier\Page\CmsPageUnifier;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class CmsPageController implements RequestHandlerInterface
{
    public function __construct(
        private CmsPageUnifier $pageUnifier,
        private HtmlResponder $html,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $page = $this->pageUnifier->unify(Route::getArgument($request, 'slugPath'));

        return $this->html->render($page->template, [
            'page' => $page,
        ], $page->page === null ? 404 : 200);
    }
}
