<?php

declare(strict_types=1);

namespace App\Components\Http\Middleware;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonErrorResponse;
use JsonException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DomainExceptionModuleHandler implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {}

    /**
     * @throws JsonException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (DomainExceptionModule $exception) {
            $this->logger->warning($exception->getMessage(), [
                'code'      => $exception->getCode(),
                'exception' => $exception,
                'url'       => (string)$request->getUri(),
            ]);

            $module = $exception->getModule() !== '' ? $exception->getModule() : 'exceptions';

            return new JsonErrorResponse(
                code: $exception->getCode(),
                message: $this->translator->trans($exception->getMessage(), [], $module),
                payload: $exception->getPayload(),
                status: 409,
            );
        }
    }
}
