<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware;

use App\Components\Http\Response\JsonErrorResponse;
use InvalidArgumentException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class InvalidArgumentExceptionHandler implements MiddlewareInterface
{
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (InvalidArgumentException $exception) {
            return new JsonErrorResponse(
                code: $exception->getCode(),
                message: $exception->getMessage(),
                payload: null,
                status: 400,
            );
        }
    }
}
