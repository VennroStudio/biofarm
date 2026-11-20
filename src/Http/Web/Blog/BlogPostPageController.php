<?php

declare(strict_types=1);

namespace App\Http\Web\Blog;

use App\Components\Router\Route;
use App\Components\Twig\HtmlResponder;
use App\Http\Unifier\BlogPage\BlogPostPageUnifier;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class BlogPostPageController implements RequestHandlerInterface
{
    public function __construct(
        private BlogPostPageUnifier $blogPostPage,
        private HtmlResponder $html,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $page = $this->blogPostPage->unify(Route::getArgument($request, 'slug'));

        return $this->html->render('pages/blog/show.html.twig', [
            'page' => $page,
        ], $page->post === null ? 404 : 200);
    }
}
