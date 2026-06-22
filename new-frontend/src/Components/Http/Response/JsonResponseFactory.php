<?php

declare(strict_types=1);

namespace App\Components\Http\Response;

use JsonException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final readonly class JsonResponseFactory
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {}

    /**
     * @param array<array-key, mixed> $payload
     */
    public function create(array $payload, int $status = 200): ResponseInterface
    {
        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new RuntimeException('Cannot encode JSON response.', 0, $exception);
        }

        $response = $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'application/json; charset=UTF-8');
        $response->getBody()->write($json);

        return $response;
    }
}
