<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class RequestIdMiddleware implements MiddlewareInterface
{
    public const string ATTRIBUTE = 'request_id';
    private const string HEADER = 'X-Request-Id';

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = $request->getHeaderLine(self::HEADER);
        if ($requestId === '') {
            $requestId = bin2hex(random_bytes(16));
        }

        return $handler
            ->handle($request->withAttribute(self::ATTRIBUTE, $requestId))
            ->withHeader(self::HEADER, $requestId);
    }
}
