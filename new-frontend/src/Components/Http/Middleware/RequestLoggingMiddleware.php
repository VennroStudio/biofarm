<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final readonly class RequestLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startedAt = hrtime(true);
        $response = $handler->handle($request);
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        $this->logger->info('HTTP request handled.', [
            'request_id'  => $request->getAttribute(RequestIdMiddleware::ATTRIBUTE),
            'method'      => $request->getMethod(),
            'path'        => $request->getUri()->getPath(),
            'status'      => $response->getStatusCode(),
            'duration_ms' => round($durationMs, 2),
        ]);

        return $response;
    }
}
