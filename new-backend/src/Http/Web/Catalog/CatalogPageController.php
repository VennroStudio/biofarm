<?php

declare(strict_types=1);

namespace App\Http\Web\Catalog;

use App\Components\Twig\HtmlResponder;
use App\Http\Unifier\Catalog\CatalogPageUnifier;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class CatalogPageController implements RequestHandlerInterface
{
    public function __construct(
        private CatalogPageUnifier $catalogPage,
        private HtmlResponder $html,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $categorySlug = $this->routeArgument($request, 'categorySlug');
        $subcategorySlug = $this->routeArgument($request, 'subcategorySlug');
        $routeComponentSlug = $this->routeArgument($request, 'componentSlug');
        $routePurposeSlug = $this->routeArgument($request, 'purposeSlug');

        return $this->html->render('pages/catalog/index.html.twig', [
            'page' => $this->catalogPage->unify(
                selectedCategory: $subcategorySlug ?? $categorySlug ?? $this->queryString($query, 'category'),
                searchQuery: $this->queryString($query, 'q'),
                sortBy: $this->queryString($query, 'sort'),
                viewMode: $this->queryString($query, 'view'),
                page: $this->queryInt($query, 'page'),
                componentSlug: $routeComponentSlug ?? $this->queryString($query, 'sostav'),
                purposeSlug: $routePurposeSlug ?? $this->queryString($query, 'dlya'),
                useFacetSeo: $routeComponentSlug !== null || $routePurposeSlug !== null,
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $query
     */
    private function queryString(array $query, string $key): ?string
    {
        $value = $query[$key] ?? null;
        if (!\is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function queryInt(array $query, string $key): ?int
    {
        $value = $query[$key] ?? null;
        if (!\is_scalar($value)) {
            return null;
        }

        $number = (int)$value;

        return $number > 0 ? $number : null;
    }

    private function routeArgument(ServerRequestInterface $request, string $key): ?string
    {
        $value = RouteContext::fromRequest($request)
            ->getRoute()
            ?->getArgument($key);

        if (!\is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
