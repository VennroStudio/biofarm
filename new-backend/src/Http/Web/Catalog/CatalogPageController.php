<?php

declare(strict_types=1);

namespace App\Http\Web\Catalog;

use App\Components\Twig\HtmlResponder;
use App\Http\Unifier\Catalog\CatalogPageUnifier;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class CatalogPageController implements RequestHandlerInterface
{
    public function __construct(
        private CatalogPageUnifier $catalogPage,
        private HtmlResponder $html,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->html->render('pages/catalog/index.html.twig', [
            'page' => $this->catalogPage->unify($this->selectedCategory($request)),
        ]);
    }

    private function selectedCategory(ServerRequestInterface $request): ?string
    {
        $value = $request->getQueryParams()['category'] ?? null;
        if (!\is_string($value)) {
            return null;
        }

        $category = trim($value);

        return $category === '' ? null : $category;
    }
}
