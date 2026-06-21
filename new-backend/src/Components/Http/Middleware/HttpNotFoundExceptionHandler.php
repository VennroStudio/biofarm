<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware;

use App\Components\Http\Response\JsonErrorResponse;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;

final readonly class HttpNotFoundExceptionHandler implements MiddlewareInterface
{
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpNotFoundException $exception) {
            return new JsonErrorResponse(
                code: $exception->getCode(),
                message: $exception->getMessage(),
                payload: null,
                status: 404,
            );
        }
    }
}
