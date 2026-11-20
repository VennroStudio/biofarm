<?php

declare(strict_types=1);

namespace App\Components\Http;

use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Handlers\ErrorHandler;
use Slim\Interfaces\CallableResolverInterface;

/** @psalm-suppress PropertyNotSetInConstructor */
class HttpErrorHandler extends ErrorHandler
{
    public const string BAD_REQUEST = 'BAD_REQUEST';
    public const string NOT_ALLOWED = 'NOT_ALLOWED';
    public const string NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    public const string RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const string SERVER_ERROR = 'SERVER_ERROR';
    public const string UNAUTHENTICATED = 'UNAUTHENTICATED';

    public function __construct(
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($callableResolver, $responseFactory, $logger);
    }

    #[Override]
    protected function respond(): ResponseInterface
    {
        $statusCode = 500;
        $type = self::SERVER_ERROR;
        $description = 'An internal error has occurred while processing your request.';
        $trace = [];

        if ($this->exception instanceof HttpException) {
            $statusCode = $this->exception->getCode();
            $description = $this->exception->getMessage();
            $type = $this->resolveType($this->exception);
        }

        if ($this->displayErrorDetails) {
            $description = $this->exception->getMessage();
            $trace = $this->exception->getTrace();
        }

        $payload = json_encode([
            'statusCode' => $statusCode,
            'error'      => [
                'type'        => $type,
                'description' => $description,
                'trace'       => $trace,
            ],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write($payload);

        return $response;
    }

    private function resolveType(HttpException $exception): string
    {
        return match (true) {
            $exception instanceof HttpNotFoundException         => self::RESOURCE_NOT_FOUND,
            $exception instanceof HttpMethodNotAllowedException => self::NOT_ALLOWED,
            $exception instanceof HttpUnauthorizedException,
            $exception instanceof HttpForbiddenException      => self::UNAUTHENTICATED,
            $exception instanceof HttpBadRequestException     => self::BAD_REQUEST,
            $exception instanceof HttpNotImplementedException => self::NOT_IMPLEMENTED,
            default                                           => self::SERVER_ERROR,
        };
    }
}
