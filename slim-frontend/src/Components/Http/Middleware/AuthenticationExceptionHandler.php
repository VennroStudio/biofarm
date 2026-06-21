<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware;

use App\Components\Exception\AuthenticationException;
use App\Components\Http\Response\JsonErrorResponse;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AuthenticationExceptionHandler implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (AuthenticationException $exception) {
            $this->logger->warning($exception->getMessage(), [
                'url' => (string)$request->getUri(),
            ]);

            return new JsonErrorResponse(
                code: $exception->getCode(),
                message: $this->translator->trans($exception->getMessage(), [], 'components'),
                payload: null,
                status: 401,
            );
        }
    }
}
