<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware\Identity;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class OptionalAuthenticate implements MiddlewareInterface
{
    public function __construct(
        private Authenticate $authenticate,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (trim($request->getHeaderLine('Authorization')) === '') {
            return $handler->handle($request);
        }

        return $handler->handle(RequestIdentity::with($request, $this->authenticate->authenticate($request)));
    }
}
