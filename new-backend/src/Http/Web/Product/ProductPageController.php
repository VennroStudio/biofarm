<?php

declare(strict_types=1);

namespace App\Http\Web\Product;

use App\Components\Router\Route;
use App\Components\Twig\HtmlResponder;
use App\Http\Unifier\ProductPage\ProductPageUnifier;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ProductPageController implements RequestHandlerInterface
{
    public function __construct(
        private ProductPageUnifier $productPage,
        private HtmlResponder $html,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $page = $this->productPage->unify(Route::getArgument($request, 'slug'));

        return $this->html->render('pages/product/show.html.twig', [
            'page' => $page,
        ], $page->product === null ? 404 : 200);
    }
}
