<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Auth;

use App\Components\Http\Cookie\CookieContext;
use App\Components\Http\Cookie\CookieManager;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class LogoutAction implements RequestHandlerInterface
{
    public function __construct(
        private CookieManager $cookieManager,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->cookieManager->discard(
            response: $this->responseFactory->createResponse(204),
            context: new CookieContext(),
        );
    }
}
