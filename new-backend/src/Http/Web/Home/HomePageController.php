<?php

declare(strict_types=1);

namespace App\Http\Web\Home;

use App\Components\Twig\HtmlResponder;
use App\Http\Unifier\Home\HomePageUnifier;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class HomePageController implements RequestHandlerInterface
{
    public function __construct(
        private HomePageUnifier $homePage,
        private HtmlResponder $html,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $category = $query['category'] ?? null;
        $page = $query['page'] ?? null;
        $catalog = $this->homePage->catalog(
            category: is_string($category) ? $category : null,
            requestedPage: is_string($page) && ctype_digit($page) ? (int) $page : 1,
            compact: ($query['per_page'] ?? null) === '6',
        );

        if ($request->getHeaderLine('X-Home-Catalog') === '1') {
            $response = $this->html->render('sections/home/catalog-content.html.twig', ['catalog' => $catalog]);
        } else {
            $response = $this->html->render('pages/home/index.html.twig', ['page' => $this->homePage->unify($catalog)]);
        }

        return $response->withAddedHeader('Vary', 'X-Home-Catalog');
    }
}
