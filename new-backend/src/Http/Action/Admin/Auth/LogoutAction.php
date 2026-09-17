<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Auth;

use App\Components\Http\Cookie\AdminSessionCookie;
use App\Modules\User\Command\Auth\Logout\LogoutCommand;
use App\Modules\User\Command\Auth\Logout\LogoutHandler;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class LogoutAction implements RequestHandlerInterface
{
    public function __construct(
        private AdminSessionCookie $cookieManager,
        private LogoutHandler $handler,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->cookieManager->read($request);
        if ($token !== '') $this->handler->handle(new LogoutCommand($token));
        return $this->cookieManager->discard($this->responseFactory->createResponse(204));
    }
}
