<?php

declare(strict_types=1);

namespace App\Http\Web\Legal;

use App\Components\Twig\HtmlResponder;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class PrivacyPolicyPageController implements RequestHandlerInterface
{
    public function __construct(
        private HtmlResponder $html,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        unset($request);

        return $this->html->render('pages/legal/privacy.html.twig');
    }
}
