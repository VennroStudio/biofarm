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
        $query = $request->getQueryParams();

        return $this->html->render('pages/catalog/index.html.twig', [
            'page' => $this->catalogPage->unify(
                selectedCategory: $this->queryString($query, 'category'),
                searchQuery: $this->queryString($query, 'q'),
                sortBy: $this->queryString($query, 'sort'),
                viewMode: $this->queryString($query, 'view'),
                page: $this->queryInt($query, 'page'),
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
}
