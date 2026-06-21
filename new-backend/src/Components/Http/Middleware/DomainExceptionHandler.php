<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware;

use App\Components\Http\Response\JsonErrorResponse;
use DomainException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final readonly class DomainExceptionHandler implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (DomainException $exception) {
            $this->logger->warning($exception->getMessage(), [
                'code'      => $exception->getCode(),
                'exception' => $exception,
                'url'       => (string)$request->getUri(),
            ]);

            return new JsonErrorResponse(
                code: $exception->getCode(),
                message: $exception->getMessage(),
                payload: null,
                status: 409,
            );
        }
    }
}
