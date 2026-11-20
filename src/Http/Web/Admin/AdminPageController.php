<?php

declare(strict_types=1);

namespace App\Http\Web\Admin;

use App\Components\Twig\HtmlResponder;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class AdminPageController implements RequestHandlerInterface
{
    public function __construct(
        private HtmlResponder $responder,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responder->render('pages/admin/app.html.twig', [
            'meta' => [
                'title'       => 'Админка БИОФАРМ',
                'description' => 'Панель управления БИОФАРМ',
            ],
        ]);
    }
}
