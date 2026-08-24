<?php

declare(strict_types=1);

namespace App\Http\Web\Faq;

use App\Components\Twig\HtmlResponder;
use App\Http\Unifier\Faq\FaqPageUnifier;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class FaqPageController implements RequestHandlerInterface
{
    public function __construct(
        private FaqPageUnifier $page,
        private HtmlResponder $html,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->html->render('pages/faq/index.html.twig', [
            'page' => $this->page->unify(),
        ]);
    }
}
