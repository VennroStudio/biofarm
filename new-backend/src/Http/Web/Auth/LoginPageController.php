<?php

declare(strict_types=1);

namespace App\Http\Web\Auth;

use App\Components\Twig\HtmlResponder;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class LoginPageController implements RequestHandlerInterface
{
    public function __construct(
        private HtmlResponder $html,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->html->render('pages/auth/login.html.twig');
    }
}
