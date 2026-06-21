<?php

declare(strict_types=1);

namespace App\Http\Action\v1;

use App\Components\Http\Response\JsonResponse;
use OpenApi\Attributes as OA;
use OpenApi\Generator;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OA\Info(
    version: '1.0',
    title: 'API'
)]
#[OA\Server(
    url: '/v1/'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    name: 'bearerAuth',
    in: 'header',
    bearerFormat: 'JWT',
    scheme: 'bearer'
)]
#[OA\Tag(
    name: 'Test',
    description: 'Тестовые эндпоинты'
)]
final class OpenApiAction implements RequestHandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $openapi = new Generator()->generate([__DIR__]);

        return new JsonResponse($openapi);
    }
}
