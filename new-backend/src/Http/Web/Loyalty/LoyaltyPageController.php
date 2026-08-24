<?php

declare(strict_types=1);

namespace App\Http\Web\Loyalty;

use App\Components\Twig\HtmlResponder;
use App\Http\Unifier\Loyalty\LoyaltyPageUnifier;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class LoyaltyPageController implements RequestHandlerInterface
{
    public function __construct(
        private LoyaltyPageUnifier $page,
        private HtmlResponder $html,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->html->render('pages/loyalty/index.html.twig', [
            'page' => $this->page->unify(),
        ]);
    }
}
