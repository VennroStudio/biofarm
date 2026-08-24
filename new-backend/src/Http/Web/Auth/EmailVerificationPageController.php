<?php

declare(strict_types=1);

namespace App\Http\Web\Auth;

use App\Components\Twig\HtmlResponder;
use App\Http\View\Auth\EmailVerificationPageView;
use App\Modules\Page\Service\PageSeoProvider;
use App\Modules\User\Command\User\EmailConfirm\EmailConfirmCommand;
use App\Modules\User\Command\User\EmailConfirm\EmailConfirmHandler;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final readonly class EmailVerificationPageController implements RequestHandlerInterface
{
    public function __construct(
        private HtmlResponder $html,
        private PageSeoProvider $seo,
        private EmailConfirmHandler $handler,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = trim((string)($request->getQueryParams()['token'] ?? ''));
        $status = 'missing';

        if ($token !== '') {
            try {
                $this->handler->handle(new EmailConfirmCommand($token));
                $status = 'success';
            } catch (Throwable) {
                $status = 'error';
            }
        }

        return $this->html->render('pages/auth/email-verification.html.twig', [
            'page' => new EmailVerificationPageView(
                meta: $this->seo->systemMeta(
                    'email_verification',
                    '/email-verification',
                    'Подтверждение email — БИОФАРМ',
                    'Страница подтверждения email БИОФАРМ.',
                    robots: 'noindex, follow',
                ),
                status: $status,
            ),
        ]);
    }
}
