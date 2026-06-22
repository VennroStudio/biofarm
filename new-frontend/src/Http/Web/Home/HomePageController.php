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
        return $this->html->render('pages/home/index.html.twig', [
            'page' => $this->homePage->unify($this->selectedCategory($request)),
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
