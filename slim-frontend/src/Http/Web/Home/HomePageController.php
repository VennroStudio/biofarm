<?php

declare(strict_types=1);

namespace App\Http\Web\Home;

use App\Components\Http\Response\HtmlResponse;
use App\Http\Unifier\Home\HomePageUnifier;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final readonly class HomePageController implements RequestHandlerInterface
{
    public function __construct(
        private HomePageUnifier $homePage,
        private Environment $twig,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->twig->render('pages/home/index.html.twig', [
            'page' => $this->homePage->unify(),
        ]));
    }
}
