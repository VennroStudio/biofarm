<?php

declare(strict_types=1);

namespace App\Http\Web\Certificate;

use App\Components\Twig\HtmlResponder;
use App\Http\Unifier\Certificate\CertificatePageUnifier;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class CertificatePageController implements RequestHandlerInterface
{
    public function __construct(
        private CertificatePageUnifier $page,
        private HtmlResponder $html,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->html->render('pages/certificates/index.html.twig', [
            'page' => $this->page->unify(),
        ]);
    }
}
