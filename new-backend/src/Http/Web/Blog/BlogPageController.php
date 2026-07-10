<?php

declare(strict_types=1);

namespace App\Http\Web\Blog;

use App\Components\Twig\HtmlResponder;
use App\Http\Unifier\BlogPage\BlogPageUnifier;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class BlogPageController implements RequestHandlerInterface
{
    public function __construct(
        private BlogPageUnifier $blogPage,
        private HtmlResponder $html,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        return $this->html->render('pages/blog/index.html.twig', [
            'page' => $this->blogPage->unify(
                isset($queryParams['category']) ? (string)$queryParams['category'] : null,
                isset($queryParams['q']) ? (string)$queryParams['q'] : null,
                isset($queryParams['page']) ? (int)$queryParams['page'] : 1,
            ),
        ]);
    }
}
